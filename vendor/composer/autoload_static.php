<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit69b051c95aa0142a17707c7bbb9985f8
{
    public static $prefixesPsr0 = array (
        'T' => 
        array (
            'Trello\\' => 
            array (
                0 => __DIR__ . '/..' . '/mattzuba/php-trello/src',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixesPsr0 = ComposerStaticInit69b051c95aa0142a17707c7bbb9985f8::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}
