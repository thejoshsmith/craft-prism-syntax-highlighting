<?php

/**
 * Prism Syntax Highlighting - Editor Service
 *
 * @link      https://www.joshsmith.dev
 * @copyright Copyright (c) 2019 Josh Smith
 */

namespace thejoshsmith\prismsyntaxhighlighting\services;

use thejoshsmith\prismsyntaxhighlighting\Plugin;
use thejoshsmith\prismsyntaxhighlighting\assetbundles\prismsyntaxhighlighting\PrismSyntaxHighlightingAsset;

use Craft;
use craft\base\Component;
use craft\web\View;
use yii\web\AssetBundle;

/**
 * Prism Syntax highlighting Editor Service
 * Responsible for editor business logic
 * @author    Josh Smith
 * @package   Prism Syntax Highlighting
 * @since     2.0.0
 */
class Editor extends Component
{
    /**
     * A static array of theme asset files to be registered
     * @var array
     */
    public static $editorThemes = [];

    /**
     * A static array of language asset files to be registered
     * @var array
     */
    public static $editorLanguages = [];

    /**
     * Returns a theme class from the passed theme name
     * @author Josh Smith <josh@batch.nz>
     * @param  string $theme Name of the theme
     * @return string
     */
    public function getThemeClass(string $theme): string
    {
        return $theme;
    }

    /**
     * Returns a language class from the passed language name
     * @author Josh Smith <josh@batch.nz>
     * @param  string $language Name of the language
     * @return string
     */
    public function getLanguageClass(string $language): string
    {
        return 'language-' . $language;
    }

    /**
     * Returns whether there are PrismJS asset files to be registered
     * @author Josh Smith <josh@batch.nz>
     * @return boolean
     */
    public function hasTemplateAssetFiles(): bool
    {
        return !empty(self::$editorThemes) || !empty(self::$editorLanguages);
    }

    /**
     * Registers field PrismJS asset files
     * This loads all assets required to use PrismJS on the front end
     * @author Josh Smith <josh@batch.nz>
     * @return void
     */
    public function registerTemplateAssetFiles(): ?AssetBundle
    {
        if( !$this->hasTemplateAssetFiles() ) return null;
        return $this->registerAssetFiles(self::$editorLanguages, self::$editorThemes);
    }

    /**
     * Registers asset files required by a prism editor
     * Defaults the languages and themes to the plugin settings
     * Takes an array of language and theme definitions and loads/publishes the required asset files into the view
     *
     * @author Josh Smith <josh@batch.nz>
     * @param  array  $editorLanguages An array of prism language definitions
     * @param  array  $editorThemes    An array of prism theme definitions
     * @return AssetBundle
     */
    public function registerAssetFiles(array $editorLanguages = [], array $editorThemes = []): AssetBundle
    {
        $settings = Plugin::$plugin->getSettings();

        if( empty($editorLanguages) ){
            $editorLanguages = $settings->editorLanguages;
        }

        if( empty($editorThemes) ){
            $editorThemes = $settings->editorThemes;
        }

        $view = Craft::$app->getView();
        $prismFilesService = Plugin::$plugin->prismFilesService;

        // Fetch the theme and language files we need to load from the definitions
        $editorThemeFiles = $prismFilesService->getEditorThemeAssetBundleFiles($editorThemes);
        $editorLanguageFiles = $prismFilesService->getEditorLanguageAssetBundleFiles($editorLanguages);

        // Register the prism asset bundle
        $assetBundle = PrismSyntaxHighlightingAsset::register($view);
        $assetBundle->js = array_merge($assetBundle->js, $editorLanguageFiles);
        $assetBundle->css = array_merge($assetBundle->css, $editorThemeFiles);

        // Register asset bundle files with the view
        $assetBundle->registerAssetFiles($view);

        return $assetBundle;
    }

     /**
     * Renders the syntax highlighting field
     * @author Josh Smith <josh@batch.nz>
     * @param  string $tag
     * @return string
     */
    public function render(string $code = '', string $editorLanguage = 'markup', string $editorTheme = 'prism')
    {
        // Keep track of the themes and languages we need to load
        // These are stored statically on this class and then registered before the view is loaded using View::EVENT_END_PAGE
        self::$editorThemes[] = $editorTheme;
        self::$editorLanguages[] = $editorLanguage;

        $oldMode = Craft::$app->getView()->getTemplateMode();
        Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_CP);

        $variables = [
            'themeClass' => $this->getThemeClass($editorTheme),
            'languageClass' => $this->getLanguageClass($editorLanguage),
            'code' => $code
        ];

        $html = Craft::$app->getView()->renderTemplate('craft-prism-syntax-highlighting/_render/codeBlock', $variables);
        Craft::$app->getView()->setTemplateMode($oldMode);

        return $html;
    }
}