<?php

namespace Domain\Entities\StepMania;

use Domain\VOs\StepMania\IArtist;
use Domain\VOs\StepMania\IBPM;
use Domain\Entities\IUser;
use Domain\Entities\IFile;

interface ISimfileBuilder
{
    public function With_Title($title);
    public function With_Artist(IArtist $artist);
    public function With_Uploader(IUser $uploader);
    public function With_BPM(IBPM $bpm);
    public function With_BpmChanges($const);
    public function With_Stops($const);
    public function With_FgChanges($const);
    public function With_BgChanges($const);
    public function With_Banner(IFile $banner = null);
    public function With_Simfile(Ifile $simfile = null);
    public function With_PackId($packId = null);
    public function With_Steps(array $steps);
    public function build();
}