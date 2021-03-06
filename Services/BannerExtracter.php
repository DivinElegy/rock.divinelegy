<?php

namespace Services;

use ZipArchive;
use finfo;
use Exception;
use Services\IBannerExtracter;
use Services\IConfigManager;
use Domain\Entities\IFileStepByStepBuilder;

//TODO: This class can probably be refactored to be nicer. Also perhaps the methods could be static?
class BannerExtracter implements IBannerExtracter
{
    private $_builder;
    private $_destinationFileName;
    private $_hash;
    private $_configManager;

    public function __construct(IFileStepByStepBuilder $builder, IConfigManager $configManager) {
        $this->_builder = $builder;
        $this->_configManager = $configManager;
    }
    
    public function extractSongBanner($zipfile, $bannerIndex) {
        $za = new ZipArchive();
        //XXX: We assume all files are zips. Should be enforced by validation elsewhere.
        $res = $za->open($zipfile);

        if($res !== true) throw new Exception ('Could not open zip for reading.');

        for($i=0; $i<$za->numFiles; $i++)
        {
            $stat = $za->statIndex($i);
            $type = @exif_imagetype('zip://' . realpath($zipfile) . '#' . $stat['name']);
            //Sometimes simfiles specify a video as their banner. Fuck dat.
            if($stat['name'] == $bannerIndex && $type !== false)
            {
                $this->_hash = md5_file('zip://' . $zipfile . '#' . $stat['name']);
                $this->_destinationFileName = $this->_hash . '.' . pathinfo($bannerIndex, PATHINFO_EXTENSION);
                $result = copy('zip://' . $zipfile . '#' . $stat['name'], $this->_configManager->getDirective('filesPath') . '/banners/' . $this->_destinationFileName);
                break;
            }
        }

        if(!isset($result) || !$result) return null;

        $finfo = new finfo(FILEINFO_MIME);
        $mimetype = $finfo->file($this->_configManager->getDirective('filesPath') . '/banners/' . $this->_destinationFileName);
        $size = filesize($this->_configManager->getDirective('filesPath') . '/banners/' . $this->_destinationFileName);
        /* @var $fff \Domain\Entities\FileStepByStepBuilder */
        return $this->_builder->With_Hash($this->_hash)
                              ->With_Path('banners')
                              ->With_Filename(basename($bannerIndex))
                              ->With_Mimetype($mimetype)
                              ->With_Size($size)
                              ->With_UploadDate(time())
                              ->build();
    }
    
    public function extractPackBanner($zipfile, $packname)
    {
        $bannerName = '';
        $za = new ZipArchive();
        //XXX: We assume all files are zips. Should be enforced by validation elsewhere.
        $res = $za->open($zipfile);

        if($res !== true) throw new Exception ('Could not open zip for reading.');
        
        for($i=0; $i<$za->numFiles; $i++)
        {
            $stat = $za->statIndex($i);
            $type = @exif_imagetype('zip://' . realpath($zipfile) . '#' . $stat['name']);

            if($type !== false)
            {
                $pathComponents = explode('/',$stat['name']);

                //replace 3spooty with packname variable
                if(count($pathComponents) == 2 && $pathComponents[0] == $packname)
                {
                    $this->_hash = md5_file('zip://' . realpath($zipfile) . '#' . $stat['name']);
                    $this->_destinationFileName = $this->_hash . '.' . pathinfo($stat['name'], PATHINFO_EXTENSION);
                    $bannerName = $pathComponents[1];
                    $result = copy('zip://' . realpath($zipfile) . '#' . $stat['name'], $this->_configManager->getDirective('filesPath') . '/banners/' . $this->_destinationFileName);
                    break;
                }
            }
        }
        
        if(!isset($result) || !$result) return null;
        
        $finfo = new finfo(FILEINFO_MIME);
        $mimetype = $finfo->file($this->_configManager->getDirective('filesPath') . '/banners/' . $this->_destinationFileName);
        $size = filesize($this->_configManager->getDirective('filesPath') . '/banners/' . $this->_destinationFileName);
        /* @var $fff \Domain\Entities\FileStepByStepBuilder */
        return $this->_builder->With_Hash($this->_hash)
                              ->With_Path('banners')
                              ->With_Filename($bannerName)
                              ->With_Mimetype($mimetype)
                              ->With_Size($size)
                              ->With_UploadDate(time())
                              ->build();
    }
    
    private function randomFilename($seed)
    {
        return sha1(mt_rand(1, 9999) . $seed . uniqid() . time());
    }
}