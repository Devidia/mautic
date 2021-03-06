<?php
/**
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\CoreBundle\IpLookup;

use Joomla\Http\HttpFactory;

/**
 * Class AbstractLocalDataLookup.
 */
abstract class AbstractLocalDataLookup extends AbstractLookup implements IpLookupFormInterface
{
    /**
     * Path to the local data store.
     *
     * @return string
     */
    abstract public function getLocalDataStoreFilepath();

    /**
     * Return the URL to manually download.
     *
     * @return string
     */
    abstract public function getRemoteDateStoreDownloadUrl();

    /**
     * @return string
     */
    public function getConfigFormService()
    {
        return 'iplookup_download_data_store_button';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getConfigFormThemes()
    {
        return [];
    }

    /**
     * Download remote data store.
     *
     * Used by the mautic:iplookup:update_data command and form fetch button (if applicable) to update local IP data stores
     *
     * @return bool
     */
    public function downloadRemoteDataStore()
    {
        $connector = HttpFactory::getHttp();
        $package   = $this->getRemoteDateStoreDownloadUrl();

        try {
            $data = $connector->get($package);
        } catch (\Exception $exception) {
            $this->logger->error('Failed to fetch remote IP data: '.$exception->getMessage());
        }

        $tempTarget     = $this->cacheDir.'/'.basename($package);
        $tempExt        = strtolower(pathinfo($package, PATHINFO_EXTENSION));
        $localTarget    = $this->getLocalDataStoreFilepath();
        $localTargetExt = strtolower(pathinfo($localTarget, PATHINFO_EXTENSION));

        try {
            $success = false;

            switch (true) {
                case $localTargetExt === $tempExt:
                    $success = (bool) file_put_contents($localTarget, $data->body);

                    break;

                case 'gz' == $tempExt:
                    if (function_exists('gzdecode')) {
                        $success = (bool) file_put_contents($localTarget, gzdecode($data->body));
                    } elseif (function_exists('gzopen')) {
                        if (file_put_contents($tempTarget, $data->body)) {
                            $bufferSize = 4096; // read 4kb at a time
                            $file       = gzopen($tempTarget, 'rb');
                            $outFile    = fopen($localTarget, 'wb');
                            while (!gzeof($file)) {
                                fwrite($outFile, gzread($file, $bufferSize));
                            }
                            fclose($outFile);
                            gzclose($file);
                            @unlink($tempTarget);
                            $success = true;
                        }
                    }

                    break;

                case 'zip' == $tempExt:
                    file_put_contents($tempTarget, $data->body);

                    $zipper = new \ZipArchive();

                    $zipper->open($tempTarget);
                    $success = $zipper->extractTo($localTarget);
                    $zipper->close();
                    @unlink($tempTarget);
                    break;
            }
        } catch (\Exception $exception) {
            error_log($exception);

            $success = false;
        }

        return $success;
    }

    /**
     * Get the common directory for data.
     *
     * @return null|string
     */
    protected function getDataDir()
    {
        if (null !== $this->cacheDir) {
            $dataDir = $this->cacheDir.'/../ip_data';
            if (!file_exists($dataDir)) {
                mkdir($dataDir);
            }

            return $dataDir;
        }

        return null;
    }
}
