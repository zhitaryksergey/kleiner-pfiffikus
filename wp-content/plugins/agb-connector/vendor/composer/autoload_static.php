<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit6596d6cee9e5b2190cbb03ca30106e46
{
    public static $prefixLengthsPsr4 = array (
        'I' => 
        array (
            'Inpsyde\\AGBConnector\\' => 21,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Inpsyde\\AGBConnector\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Inpsyde\\AGBConnector\\CustomExceptions\\ActionTagException' => __DIR__ . '/../..' . '/src/CustomExceptions/ActionTagException.php',
        'Inpsyde\\AGBConnector\\CustomExceptions\\AuthException' => __DIR__ . '/../..' . '/src/CustomExceptions/AuthException.php',
        'Inpsyde\\AGBConnector\\CustomExceptions\\ConfigurationException' => __DIR__ . '/../..' . '/src/CustomExceptions/ConfigurationException.php',
        'Inpsyde\\AGBConnector\\CustomExceptions\\CountryException' => __DIR__ . '/../..' . '/src/CustomExceptions/CountryException.php',
        'Inpsyde\\AGBConnector\\CustomExceptions\\CredentialsException' => __DIR__ . '/../..' . '/src/CustomExceptions/CredentialsException.php',
        'Inpsyde\\AGBConnector\\CustomExceptions\\GeneralException' => __DIR__ . '/../..' . '/src/CustomExceptions/GeneralException.php',
        'Inpsyde\\AGBConnector\\CustomExceptions\\HtmlTagException' => __DIR__ . '/../..' . '/src/CustomExceptions/HtmlTagException.php',
        'Inpsyde\\AGBConnector\\CustomExceptions\\LanguageException' => __DIR__ . '/../..' . '/src/CustomExceptions/LanguageException.php',
        'Inpsyde\\AGBConnector\\CustomExceptions\\NotSimpleXmlInstanceException' => __DIR__ . '/../..' . '/src/CustomExceptions/NotSimpleXmlInstanceException.php',
        'Inpsyde\\AGBConnector\\CustomExceptions\\PdfFilenameException' => __DIR__ . '/../..' . '/src/CustomExceptions/PdfFilenameException.php',
        'Inpsyde\\AGBConnector\\CustomExceptions\\PdfMD5Exception' => __DIR__ . '/../..' . '/src/CustomExceptions/PdfMD5Exception.php',
        'Inpsyde\\AGBConnector\\CustomExceptions\\PdfUrlException' => __DIR__ . '/../..' . '/src/CustomExceptions/PdfUrlException.php',
        'Inpsyde\\AGBConnector\\CustomExceptions\\PostPageException' => __DIR__ . '/../..' . '/src/CustomExceptions/PostPageException.php',
        'Inpsyde\\AGBConnector\\CustomExceptions\\TextException' => __DIR__ . '/../..' . '/src/CustomExceptions/TextException.php',
        'Inpsyde\\AGBConnector\\CustomExceptions\\TextTypeException' => __DIR__ . '/../..' . '/src/CustomExceptions/TextTypeException.php',
        'Inpsyde\\AGBConnector\\CustomExceptions\\TitleException' => __DIR__ . '/../..' . '/src/CustomExceptions/TitleException.php',
        'Inpsyde\\AGBConnector\\CustomExceptions\\VersionException' => __DIR__ . '/../..' . '/src/CustomExceptions/VersionException.php',
        'Inpsyde\\AGBConnector\\CustomExceptions\\WPFilesystemException' => __DIR__ . '/../..' . '/src/CustomExceptions/WPFilesystemException.php',
        'Inpsyde\\AGBConnector\\CustomExceptions\\XmlApiException' => __DIR__ . '/../..' . '/src/CustomExceptions/XmlApiException.php',
        'Inpsyde\\AGBConnector\\Install' => __DIR__ . '/../..' . '/src/Install.php',
        'Inpsyde\\AGBConnector\\Middleware\\CheckActionXml' => __DIR__ . '/../..' . '/src/Middleware/CheckActionXml.php',
        'Inpsyde\\AGBConnector\\Middleware\\CheckAuthXml' => __DIR__ . '/../..' . '/src/Middleware/CheckAuthXml.php',
        'Inpsyde\\AGBConnector\\Middleware\\CheckConfiguration' => __DIR__ . '/../..' . '/src/Middleware/CheckConfiguration.php',
        'Inpsyde\\AGBConnector\\Middleware\\CheckCountrySetXml' => __DIR__ . '/../..' . '/src/Middleware/CheckCountrySetXml.php',
        'Inpsyde\\AGBConnector\\Middleware\\CheckCredentialsXml' => __DIR__ . '/../..' . '/src/Middleware/CheckCredentialsXml.php',
        'Inpsyde\\AGBConnector\\Middleware\\CheckHtmlXml' => __DIR__ . '/../..' . '/src/Middleware/CheckHtmlXml.php',
        'Inpsyde\\AGBConnector\\Middleware\\CheckInstanceSimpleXml' => __DIR__ . '/../..' . '/src/Middleware/CheckInstanceSimpleXml.php',
        'Inpsyde\\AGBConnector\\Middleware\\CheckLanguageXml' => __DIR__ . '/../..' . '/src/Middleware/CheckLanguageXml.php',
        'Inpsyde\\AGBConnector\\Middleware\\CheckPdfFilenameXml' => __DIR__ . '/../..' . '/src/Middleware/CheckPdfFilenameXml.php',
        'Inpsyde\\AGBConnector\\Middleware\\CheckPdfUrlXml' => __DIR__ . '/../..' . '/src/Middleware/CheckPdfUrlXml.php',
        'Inpsyde\\AGBConnector\\Middleware\\CheckPostXml' => __DIR__ . '/../..' . '/src/Middleware/CheckPostXml.php',
        'Inpsyde\\AGBConnector\\Middleware\\CheckTextTypeXml' => __DIR__ . '/../..' . '/src/Middleware/CheckTextTypeXml.php',
        'Inpsyde\\AGBConnector\\Middleware\\CheckTextXml' => __DIR__ . '/../..' . '/src/Middleware/CheckTextXml.php',
        'Inpsyde\\AGBConnector\\Middleware\\CheckTitleXml' => __DIR__ . '/../..' . '/src/Middleware/CheckTitleXml.php',
        'Inpsyde\\AGBConnector\\Middleware\\CheckVersionXml' => __DIR__ . '/../..' . '/src/Middleware/CheckVersionXml.php',
        'Inpsyde\\AGBConnector\\Middleware\\Middleware' => __DIR__ . '/../..' . '/src/Middleware/Middleware.php',
        'Inpsyde\\AGBConnector\\Middleware\\MiddlewareInterface' => __DIR__ . '/../..' . '/src/Middleware/MiddlewareInterface.php',
        'Inpsyde\\AGBConnector\\Middleware\\MiddlewareRequestHandler' => __DIR__ . '/../..' . '/src/Middleware/MiddlewareRequestHandler.php',
        'Inpsyde\\AGBConnector\\Plugin' => __DIR__ . '/../..' . '/src/Plugin.php',
        'Inpsyde\\AGBConnector\\Settings' => __DIR__ . '/../..' . '/src/Settings.php',
        'Inpsyde\\AGBConnector\\ShortCodes' => __DIR__ . '/../..' . '/src/ShortCodes.php',
        'Inpsyde\\AGBConnector\\XmlApi' => __DIR__ . '/../..' . '/src/XmlApi.php',
        'Inpsyde\\AGBConnector\\XmlApiSupportedService' => __DIR__ . '/../..' . '/src/XmlApiSupportedService.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit6596d6cee9e5b2190cbb03ca30106e46::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit6596d6cee9e5b2190cbb03ca30106e46::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit6596d6cee9e5b2190cbb03ca30106e46::$classMap;

        }, null, ClassLoader::class);
    }
}
