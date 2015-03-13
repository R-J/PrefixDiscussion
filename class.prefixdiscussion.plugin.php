<?php defined('APPLICATION') or die;

$PluginInfo['PrefixDiscussion'] = array(
    'Name' => 'PrefixDiscussion',
    'Description' => 'Allows prefixing discussion titles with a configurable set of terms.',
    'Version' => '0.2',
    'RequiredApplications' => array('Vanilla' => '2.1'),
    'MobileFriendly' => true,
    'HasLocale' => true,
    'RegisterPermissions' => array(
        'Vanilla.PrefixDiscussion.Add',
        'Vanilla.PrefixDiscussion.View',
        'Vanilla.PrefixDiscussion.Manage'),    
    'Author' => 'Robin Jurinka',
    'SettingsUrl' => '/dashboard/settings/PrefixDiscussion',
    'SettingsPermission' => 'Vanilla.PrefixDiscussion.Manage',
    'License' => 'MIT'
);

/**
 * PrefixDiscussion allows users to add prefixes to discussions.
 *
 * Permissions must be set properly in order to a) allow adding, b) allow viewing
 * and c) allow managing prefixes
 *
 * @package PrefixDiscussion
 * @author Robin Jurinka
 * @license MIT
 */
class PrefixDiscussionPlugin extends Gdn_Plugin {
    /**
     * Build array of prefixes on each instantiation.
     *
     * @return Array of Prefixes
     * @package PrefixDiscussion
     * @since 0.2
     */
    public function GetPrefixes () {
        // get prefixes from config
        $Prefixes = array_filter(
            explode(
                Gdn::Config('Plugins.PrefixDiscussion.ListSeparator', ';'),
                Gdn::Config(
                    'Plugins.PrefixDiscussion.Prefixes',
                    'Question;Solved'
                )
            )
        );
        array_unshift($Prefixes, T('PrefixDiscussion.None', '-'));
        return array_combine($Prefixes, $Prefixes);
    }


    /**
     * Setup is called when plugin is enabled and prepares config and db.
     *
     * @package PrefixDiscussion
     * @since 0.1
     */
    public function setup () {
        // init some config settings
        if (!C('Plugins.PrefixDiscussion.ListSeparator')) {
            SaveToConfig('Plugins.PrefixDiscussion.ListSeparator', ';');
        }      
        if (!C('Plugins.PrefixDiscussion.Prefixes')) {
            SaveToConfig(
                'Plugins.PrefixDiscussion.Prefixes',
                'Question;Solved'
            );
        }      
        // change db structure
        $this->structure();
    }


    /**
     * Structure is called by setup() and adds column to discussion table.
     *
     * @package PrefixDiscussion
     * @since 0.1
     */
    public function structure() {
        Gdn::Database()->Structure()
            ->Table('Discussion')
            ->Column('Prefix', 'varchar(64)', true)
            ->Set();
    }


    /**
     * Barebone config screen.
     *
     * @package PrefixDiscussion
     * @since 0.1
     */
    public function settingsController_PrefixDiscussion_create ($Sender) {
        $Sender->Permission('Vanilla.PrefixDiscussion.Manage');
        $Sender->SetData('Title', T('Prefix Discussion Settings'));
        $Sender->AddSideMenu('dashboard/settings/plugins');
        $Conf = new ConfigurationModule($Sender);
        $Conf->Initialize(array(
            'Plugins.PrefixDiscussion.ListSeparator',
            'Plugins.PrefixDiscussion.Prefixes'
        ));
        $Conf->RenderAll();
    }


    /**
     * Render input box.
     *
     * @param object $Sender PostController.
     * @package PrefixDiscussion
     * @since 0.1
     */
    public function postController_beforeBodyInput_handler ($Sender) {
        // only show dropdown if permission is set
        if (!CheckPermission('Vanilla.PrefixDiscussion.Add')) {
            return;
        }
        // maybe someone wants to style that
        $Sender->AddCssFile('prefixdiscussion.css', 'plugins/PrefixDiscussion');
$Sender->AddJsFile('prefixdiscussion.js', 'plugins/PrefixDiscussion');
        // render output
        echo '<div class="P PrefixDiscussion">';
        echo $Sender->Form->Label(T('Discussion Prefix'), 'Prefix');
        echo $Sender->Form->DropDown('Prefix', $this->GetPrefixes());
        echo '</div>';
    }


    /**
     * Add prefix to discussion title.
     *
     * @param object $Sender PostController.
     * @package PrefixDiscussion
     * @since 0.1
     */
    public function discussionController_beforeDiscussionRender_handler ($Sender) {
        if (!CheckPermission('Vanilla.PrefixDiscussion.View')) {
            return;
        }
        $Sender->AddCssFile('prefixdiscussion.css', 'plugins/PrefixDiscussion');
        $Prefix = $Sender->Discussion->Prefix;
        if ($Prefix == '') {
            return;
        }
        $Sender->Discussion->Name = Wrap(
            $Prefix,
            'span',
            array('class' => "PrefixDiscussion Sp{$Prefix}")
        ).$Sender->Discussion->Name;
   }


    /**
     * Add prefix to discussions lists.
     *
     * @param object $Sender Vanilla controller.
     * @package PrefixDiscussion
     * @since 0.1
     */
    public function base_beforeDiscussionName_handler ($Sender) {
        if (!CheckPermission('Vanilla.PrefixDiscussion.View')) {
            return;
        }
        $Prefix = $Sender->EventArguments['Discussion']->Prefix;
        if ($Prefix == '') {
            return;
        }
        $Sender->EventArguments['Discussion']->Name = Wrap(
            $Prefix,
            'span',
            array('class' => "PrefixDiscussion Sp{$Prefix}")
        ).$Sender->EventArguments['Discussion']->Name;
    }


    /**
     * Add css to discussions list if needed.
     *
     * @param object $Sender DiscussionsController.
     * @package PrefixDiscussion
     * @since 0.1
     */
    public function discussionsController_render_before ($Sender) {
        if (!CheckPermission('Vanilla.PrefixDiscussion.View')) {
            return;
        }
        $Sender->AddCssFile('prefixdiscussion.css', 'plugins/PrefixDiscussion');
// $Sender->AddJsFile('prefixdiscussion.js', 'plugins/PrefixDiscussion');
    }


    /**
     * Add css to categories list if needed.
     *
     * @param object $Sender DiscussionsController.
     * @package PrefixDiscussion
     * @since 0.1
     */
    public function categoriesController_render_before ($Sender) {
        if (!CheckPermission('Vanilla.PrefixDiscussion.View')) {
            return;
        }
        $Sender->AddCssFile('prefixdiscussion.css', 'plugins/PrefixDiscussion');
    }


    /**
     * Delete "no prefix" placeholder from form fields before it gets written to db.
     *
     * @param object $Sender DiscussionController.
     * @package PrefixDiscussion
     * @since 0.1
     */
    public function discussionModel_beforeSaveDiscussion_handler ($Sender) {
        if ($Sender->EventArguments['FormPostValues']['Prefix'] == T('PrefixDiscussion.None', '-')) {
            $Sender->EventArguments['FormPostValues']['Prefix'] = '';
        }
    }
}
