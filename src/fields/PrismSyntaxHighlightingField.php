<?php
/**
 * Prism Syntax Highlighting plugin for Craft CMS 3.x
 *
 * Adds a new field type that provides syntax highlighting capabilities using PrismJS.
 *
 * @link      https://www.joshsmith.dev
 * @copyright Copyright (c) 2019 Josh Smith <me@joshsmith.dev>
 */

namespace thejoshsmith\prismsyntaxhighlighting\fields;

use thejoshsmith\prismsyntaxhighlighting\Plugin;
use thejoshsmith\prismsyntaxhighlighting\models\PrismField;
use thejoshsmith\prismsyntaxhighlighting\assetbundles\prismsyntaxhighlighting\PrismSyntaxHighlightingAsset;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\Db;
use craft\helpers\Json;
use yii\db\Schema;
use yii\web\AssetBundle;

/**
 * @author    Josh Smith <me@joshsmith.dev>
 * @package   PrismSyntaxHighlighting
 * @since     1.0.0
 */
class PrismSyntaxHighlightingField extends Field
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $defaultEditorTheme = '';

    /**
     * @var string
     */
    public $defaultEditorLanguage = '';

    /**
     * @var string
     */
    public $editorHeight = '4';

    /**
     * @var string
     */
    public $editorTabWidth = '4';

    /**
     * @var array
     */
    public $editorPlugins = [];

    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('craft-prism-syntax-highlighting', 'Prism Syntax Highlighting');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules = array_merge($rules, [
            ['editorHeight', 'string'],
            ['editorHeight', 'default', 'value' => '4'],
            ['editorTabWidth', 'string'],
            ['editorTabWidth', 'default', 'value' => '4']
        ]);
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getContentColumnType(): string
    {
        return Schema::TYPE_TEXT;
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        if( is_string($value) ){
            $value = Json::decode($value, true);
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function serializeValue($value, ElementInterface $element = null)
    {
        return parent::serializeValue($value, $element);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        $settings = Plugin::$plugin->getSettings();

        // Render the settings template
        return Craft::$app->getView()->renderTemplate(
            'craft-prism-syntax-highlighting/_components/fields/PrismSyntaxHighlightingField_settings',
            [
                'field' => $this,
                'defaults' => $settings
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        $settings = Plugin::$plugin->getSettings();
        $prismEditorService = Plugin::$plugin->prismEditorService;

        // Register asset files
        $prismEditorService->registerAssetFiles();

        // Get our id and namespace
        $id = Craft::$app->getView()->formatInputId($this->handle);
        $namespacedId = Craft::$app->getView()->namespaceInputId($id);

        // Variables to pass down to our field JavaScript to let it namespace properly
        $jsonVars = [
            'id' => $id,
            'name' => $this->handle,
            'namespace' => $namespacedId,
            'prefix' => Craft::$app->getView()->namespaceInputId(''),
        ];

        $jsonVars = Json::encode($jsonVars);
        Craft::$app->getView()->registerJs("$('#{$namespacedId}-field').PrismSyntaxHighlightingField(" . $jsonVars . ");");

        // Set editor theme and language
        $editorTheme = (empty($value['editorTheme']) ? $this->defaultEditorTheme : $value['editorTheme']);
        $editorLanguage = (empty($value['editorLanguage']) ? $this->defaultEditorLanguage : $value['editorLanguage']);

        // Register plugin hooks
        // $hookId = uniqid();
        // $prismEditorService->registerPluginClassHooks($hookId);
        // $prismEditorService->registerPluginInputHtmlHooks($this);

        // Render the input template
        return Craft::$app->getView()->renderTemplate(
            'craft-prism-syntax-highlighting/_components/fields/PrismSyntaxHighlightingField_input',
            [
                'name' => $this->handle,
                'value' => $value,
                'field' => $this,
                'id' => $id,
                // 'hookId' => $hookId,
                'namespacedId' => $namespacedId,
                'code' => $value['code'] ?? '',
                'editorLanguageClass' => $prismEditorService->getLanguageClass($editorLanguage),
                'editorThemeClass' => $prismEditorService->getThemeClass($editorTheme),
                'editorTheme' => $editorTheme,
                'editorLanguage' => $editorLanguage,
                'editorPlugins' => $value['editorPlugins'] ?? $this->editorPlugins,
                'editorHeight' => $this->editorHeight,
                'editorTabWidth' => $this->editorTabWidth,
                'settings' => $settings
            ]
        );
    }
}
