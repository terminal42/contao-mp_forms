<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\Storage\StorageIdentifierGenerator;

use Terminal42\MultipageFormsBundle\FormManager;

class StorageIdentifierGenerator implements StorageIdentifierGeneratorInterface
{
    public function generate(FormManager $manager): string
    {
        $info = [];
        $info[] = $manager->getFormId();
        $info[] = $manager->getSessionReference();

        // Ensure the identifier changes, when the fields are updated as the settings
        // might change
        foreach ($manager->getFormFieldModels() as $fieldModel) {
            $info[] = $fieldModel->tstamp;
        }

        return sha1(implode(';', $info));
    }
}
