<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\Step;

use Symfony\Component\Filesystem\Filesystem;

/**
 * This class expects $parameters to be in the format of.
 *
 * array<string, array<array{name: string, type: string, tmp_name: string, error:
 * int, size: int, uploaded: bool, uuid: ?string, stream: ?resource}>>
 *
 * as provided by the FileUploadNormalizer service. Meaning that every file upload
 * can contain multiple files.
 */
class FileParameterBag extends ParameterBag
{
    /**
     * PHP deletes file uploads after the request ends which is a problem for mp_forms
     * as it wants to keep them across requests. Depending on how a form upload field
     * is configured, however, a file might already have been moved to a final
     * destination. So we only do this if it was uploaded in the current request.
     */
    public function set(string $name, mixed $value): self
    {
        if (!\is_array($value)) {
            throw new \InvalidArgumentException('$value must be an array normalized by the FileUploadNormalizer service.');
        }

        foreach ($value as $k => $upload) {
            if (!\is_array($upload) || !\array_key_exists('tmp_name', $upload)) {
                throw new \InvalidArgumentException('$value must be an array normalized by the FileUploadNormalizer service.');
            }

            if (is_uploaded_file($upload['tmp_name'])) {
                $target = (new Filesystem())->tempnam(sys_get_temp_dir(), 'nc');
                move_uploaded_file($upload['tmp_name'], $target);
                $value[$k]['tmp_name'] = $target;
            }
        }

        return parent::set($name, $value);
    }
}
