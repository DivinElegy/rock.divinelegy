<?php

namespace Domain\Entities\StepMania;

use Domain\Entities\StepMania\Pack;
use Domain\Entities\IUser;

interface IPackFactory
{
    public function createInstance(
        $title,
        IUser $uploader,
        array $simfiles,
        IFile $file = null
    );
}

class PackFactory implements IPackFactory
{
    public function createInstance(
        $title,
        IUser $uploader,
        array $simfiles,
        IFile $file = null
    ) {
        return new Pack(
            $title,
            $uploader,
            $simfiles,
            $file
        );
    }
}