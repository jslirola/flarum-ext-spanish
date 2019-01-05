<?php
/*
 * This file is part of DaRkFoxDeveloper/FlarumExtSpanish.
 * -----------------------------------------------------------------------
 * Copyright © 2015-2019 Toby Zerner and Flarum
 * Copyright © 2015-2019 DaRkFoxDeveloper
 * -----------------------------------------------------------------------
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DaRkFoxDeveloper\FlarumExtSpanish;

use Illuminate\Contracts\Events\Dispatcher;
use Flarum\Extend\Frontend;
use Flarum\Settings\SettingsRepositoryInterface;
use DirectoryIterator;
use Flarum\Event\ConfigureLocales;

return [
  (new Frontend('admin'))->js(__DIR__ . '/js/dist/admin.js')
  ,function (Dispatcher $events) {
    $events->subscribe(SpanishLang::class);
  },
];

class SpanishLang {
    protected $settings;

    public function __construct(SettingsRepositoryInterface $settings) {
        $this->settings = $settings;
    }

    public function subscribe(Dispatcher $events) {
       //$events->listen(ConfigureClientView::class, [$this, 'addSettingsPage']);
       $events->listen(ConfigureLocales::class, [$this, 'addLocale']);
    }

    /*public function addSettingsPage(ConfigureClientView $event) {
        if ($event->isAdmin()) {
            $event->addAssets([
                __DIR__ . '/js/dist/admin.js'
            ]);
            $event->addBootstrapper('darkfoxdeveloper-flarum-ext-spanish/js/dist/admin');
        }
    }*/

    public function addLocale(ConfigureLocales $event) {
        $name = $title = basename(__DIR__);

        if (file_exists($manifest = __DIR__.'/composer.json')) {
            $json = json_decode(file_get_contents($manifest), true);

            if (empty($json)) {
                throw new RuntimeException("Error parsing composer.json in $name: ".json_last_error_msg());
            }

            $locale = array_get($json, 'extra.flarum-locale.code');
            $title = array_get($json, 'extra.flarum-locale.title', $title);
        }

        if (! isset($locale)) {
            throw new RuntimeException("Language pack $name must define \"extra.flarum-locale.code\" in composer.json.");
        }

        $event->locales->addLocale($locale, $title);
        $mode = $this->settings->get('darkfoxdeveloper-spanish.mode') ? $this->settings->get('darkfoxdeveloper-spanish.mode') : 'formal';

        if (! is_dir($localeDir = __DIR__.'/locale/' . $mode)) {
            throw new RuntimeException("Language pack $name must have a \"locale\" subdirectory.");
        }

        if (file_exists($file = $localeDir.'/config.js')) {
            $event->locales->addJsFile($locale, $file);
        }

        foreach (new DirectoryIterator($localeDir) as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['yml', 'yaml'])) {
                $event->locales->addTranslations($locale, $file->getPathname());
            }
        }
    }
}
