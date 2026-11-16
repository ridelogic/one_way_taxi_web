<?php

namespace App\Support;

use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Throwable;

/**
 * Mobile app store links and QR code. Nothing is shown as downloadable unless a real store URL
 * has been configured in Admin → Settings → Mobile app.
 */
class AppLinks
{
    public function __construct(private readonly Settings $settings) {}

    public function playStore(): ?string
    {
        return $this->settings->get('app_play_store_url') ?: null;
    }

    public function appStore(): ?string
    {
        return $this->settings->get('app_app_store_url') ?: null;
    }

    public function available(): bool
    {
        return (bool) ($this->playStore() || $this->appStore());
    }

    /** URL the QR code points to: the Download App page when both stores exist, else the single store link. */
    public function qrTarget(): ?string
    {
        if ($this->playStore() && $this->appStore()) {
            return route('app');
        }

        return $this->playStore() ?? $this->appStore();
    }

    /** Uploaded QR image URL, or an inline SVG data URI generated from the real store link. */
    public function qrSrc(): ?string
    {
        if ($uploaded = $this->settings->media('app_qr_media_id')) {
            return $uploaded->safeUrl();
        }

        $target = $this->qrTarget();
        if (! $target) {
            return null;
        }

        try {
            $options = new QROptions([
                'outputType' => QROutputInterface::MARKUP_SVG,
                'outputBase64' => true,
                'eccLevel' => \chillerlan\QRCode\Common\EccLevel::M,
                'addQuietzone' => true,
                'drawLightModules' => true,
                'markupDark' => '#0b1426',
                'markupLight' => '#ffffff',
            ]);

            return (new QRCode($options))->render($target);
        } catch (Throwable) {
            return null;
        }
    }
}
