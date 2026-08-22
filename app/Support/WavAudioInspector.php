<?php

declare(strict_types=1);

namespace App\Support;

/**
 * WAV(RIFF/WAVE) ファイルのヘッダーを読み、再生時間(秒)を求める。
 * 拡張子ではなくファイル本体の中身で WAV であることを確認するためにも使う。
 */
final class WavAudioInspector
{
    /**
     * @return float|null 秒数。WAV として解釈できなければ null
     */
    public static function durationSeconds(string $path): ?float
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return null;
        }

        try {
            $header = fread($handle, 12);
            if ($header === false || strlen($header) < 12) {
                return null;
            }
            if (substr($header, 0, 4) !== 'RIFF' || substr($header, 8, 4) !== 'WAVE') {
                return null;
            }

            $byteRate  = null;
            $dataBytes = null;

            while (!feof($handle)) {
                $chunkHeader = fread($handle, 8);
                if ($chunkHeader === false || strlen($chunkHeader) < 8) {
                    break;
                }
                $chunkId   = substr($chunkHeader, 0, 4);
                $chunkSize = unpack('V', substr($chunkHeader, 4, 4));
                $chunkSize = $chunkSize === false ? 0 : (int) $chunkSize[1];

                if ($chunkId === 'fmt ') {
                    $fmt = fread($handle, $chunkSize);
                    if ($fmt === false || strlen($fmt) < 16) {
                        return null;
                    }
                    $unpacked = unpack('vaudioFormat/vnumChannels/VsampleRate/VbyteRate', $fmt);
                    if ($unpacked === false) {
                        return null;
                    }
                    $byteRate = (int) $unpacked['byteRate'];
                } elseif ($chunkId === 'data') {
                    $dataBytes = $chunkSize;
                    // data チャンクの実体を読み飛ばす必要はない（duration 計算に必要な情報は揃った）
                    break;
                } else {
                    if (fseek($handle, $chunkSize, SEEK_CUR) !== 0) {
                        break;
                    }
                }
            }

            if ($byteRate === null || $byteRate <= 0 || $dataBytes === null) {
                return null;
            }

            return $dataBytes / $byteRate;
        } finally {
            fclose($handle);
        }
    }
}
