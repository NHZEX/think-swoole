<?php

namespace {

    if (!function_exists('swoole_cpu_num')) {
        function swoole_cpu_num(): int
        {
            return 1;
        }
    }

    if (!defined('SWOOLE_SOCK_TCP')) {
        define('SWOOLE_SOCK_TCP', 1);
    }

    if (!defined('SWOOLE_PROCESS')) {
        define('SWOOLE_PROCESS', 3);
    }

    if (!defined('SWOOLE_HOOK_ALL')) {
        define('SWOOLE_HOOK_ALL', 1879048191);
    }
}

namespace think\swoole\helper {

    use Psr\Http\Message\StreamInterface;
    use think\swoole\response\File;
    use think\swoole\response\Stream;

    function download(string $filename, string $name = '', $disposition = File::DISPOSITION_ATTACHMENT): File
    {
        $response = new File($filename, $disposition);

        if ($name) {
            $response->setContentDisposition($disposition, $name);
        }

        return $response;
    }

    function file(string $filename)
    {
        return new File($filename);
    }

    function stream(StreamInterface $stream, int $chunkSize = 1024, bool $readByLine = false)
    {
        return new Stream($stream, $chunkSize, $readByLine);
    }
}
