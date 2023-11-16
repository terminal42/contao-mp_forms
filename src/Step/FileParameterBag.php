<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\Step;

use Symfony\Component\Filesystem\Filesystem;

class FileParameterBag extends ParameterBag
{
    /**
     * PHP deletes file uploads after the request ends which is a problem
     * for mp_forms as it wants to keep them across requests.
     * Depending on how a form upload field is configured, however, a file might
     * already have been moved to a final destination. So we only do this if
     * it was uploaded in the current request.
     */
    public function set(string $name, mixed $value): self
    {
        if (
            \is_array($value)
            && \array_key_exists('tmp_name', $value)
            && \is_string($value['tmp_name'])
            && is_uploaded_file($value['tmp_name'])
        ) {
            $target = (new Filesystem())->tempnam(sys_get_temp_dir(), 'nc');
            move_uploaded_file($value['tmp_name'], $target);
            $value['tmp_name'] = $target;
        }

        return parent::set($name, $value);
    }
}
