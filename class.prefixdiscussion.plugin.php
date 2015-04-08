<?php defined('APPLICATION') or die;

$PluginInfo['PrefixDiscussion'] = array(
    'Name' => 'PrefixDiscussion',
    'Description' => 'Allows prefixing discussion titles with a configurable set of terms.',
    'Version' => '0.4',
    'RequiredApplications' => array('Vanilla' => '2.1'),
    'MobileFriendly' => true,
    'HasLocale' => true,
    'RegisterPermissions' => array(
        'Vanilla.PrefixDiscussion.Add',
        'Vanilla.PrefixDiscussion.View',
        'Vanilla.PrefixDiscussion.Manage'
    ),
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
    public function getPrefixes () {
        // get prefixes from config
        $prefixes = array_filter(
            explode(
                Gdn::config('Plugins.PrefixDiscussion.ListSeparator', ';'),
                Gdn::config(
                    'Plugins.PrefixDiscussion.Prefixes',
                    'Question;Solved'
                )
            )
        );
        array_unshift($prefixes, t('PrefixDiscussion.None', '-'));
        return array_combine($prefixes, $prefixes);
    }


    /**
     * Setup is called when plugin is enabled and prepares config and db.
     *
     * @package PrefixDiscussion
     * @since 0.1
     */
    public function setup () {
        // init some config settings
        if (!c('Plugins.PrefixDiscussion.ListSeparator')) {
            saveToConfig('Plugins.PrefixDiscussion.ListSeparator', ';');
        }
        if (!c('Plugins.PrefixDiscussion.Prefixes')) {
            saveToConfig(
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
    public function structure () {
        Gdn::database()->structure()
            ->table('Discussion')
            ->column('Prefix', 'varchar(64)', true)
            ->set();
    }


    /**
     * Barebone config screen.
     *
     * @param object $sender SettingsController.
     * @package PrefixDiscussion
     * @since 0.1
     */
    public function settingsController_prefixDiscussion_create ($sender) {
        $sender->permission('Vanilla.PrefixDiscussion.Manage');
        $sender->setData('Title', t('Prefix Discussion Settings'));
        $sender->addSideMenu('dashboard/settings/plugins');
        $configurationModule = new ConfigurationModule($sender);
        $configurationModule->initialize(array(
            'Plugins.PrefixDiscussion.ListSeparator',
            'Plugins.PrefixDiscussion.Prefixes'
        ));
        $configurationModule->renderAll();
    }


    /**
     * Render input box.
     *
     * @param object $sender PostController.
     * @package PrefixDiscussion
     * @since 0.1
     */
    public function postController_beforeBodyInput_handler ($sender) {
        // only show dropdown if permission is set
        if (!checkPermission('Vanilla.PrefixDiscussion.Add')) {
            return;
        }
        // maybe someone wants to style that
        $sender->addCssFile('prefixdiscussion.css', 'plugins/PrefixDiscussion');

        // render output
        echo '<div class="P PrefixDiscussion">';
        echo $sender->Form->label(t('Discussion Prefix'), 'Prefix');
        echo $sender->Form->dropDown('Prefix', $this->getPrefixes());
        echo '</div>';
    }


    /**
     * Add prefix to discussion title.
     *
     * @param object $sender PostController.
     * @package PrefixDiscussion
     * @since 0.1
     */
    public function discussionController_beforeDiscussionRender_handler ($sender) {
        if (!checkPermission('Vanilla.PrefixDiscussion.View')) {
            return;
        }
        $sender->addCssFile('prefixdiscussion.css', 'plugins/PrefixDiscussion');
        $prefix = $sender->Discussion->Prefix;
        if ($prefix == '') {
            return;
        }
        $sender->Discussion->Name = Wrap(
            $prefix,
            'span',
            array('class' => 'PrefixDiscussion Sp'.str_replace(' ', '_', $prefix))
        ).$sender->Discussion->Name;
    }


    /**
     * Add prefix to discussions lists.
     *
     * Does not work for table view since there is no appropriate event
     * in Vanilla 2.1.
     *
     * @param object $sender Vanilla controller.
     * @package PrefixDiscussion
     * @since 0.1
     */
    public function base_beforeDiscussionName_handler ($sender) {
        if (!checkPermission('Vanilla.PrefixDiscussion.View')) {
            return;
        }
        $prefix = $sender->EventArguments['Discussion']->Prefix;
        if ($prefix == '') {
            return;
        }
        $sender->EventArguments['Discussion']->Name = Wrap(
            $prefix,
            'span',
            array('class' => 'PrefixDiscussion Sp'.str_replace(' ', '_', $prefix))
        ).$sender->EventArguments['Discussion']->Name;
    }


    /**
     * Add css to discussions list if needed.
     *
     * @param object $sender DiscussionsController.
     * @package PrefixDiscussion
     * @since 0.1
     */
    public function discussionsController_render_before ($sender) {
        if (!checkPermission('Vanilla.PrefixDiscussion.View')) {
            return;
        }
        $sender->addCssFile('prefixdiscussion.css', 'plugins/PrefixDiscussion');
    }


    /**
     * Add css to categories list if needed.
     *
     * @param object $sender DiscussionsController.
     * @package PrefixDiscussion
     * @since 0.1
     */
    public function categoriesController_render_before ($sender) {
        if (!checkPermission('Vanilla.PrefixDiscussion.View')) {
            return;
        }
        $sender->addCssFile('prefixdiscussion.css', 'plugins/PrefixDiscussion');
    }


    /**
     * Delete "no prefix" placeholder from form fields before it gets written to db.
     *
     * @param object $sender DiscussionController.
     * @package PrefixDiscussion
     * @since 0.1
     */
    public function discussionModel_beforeSaveDiscussion_handler ($sender) {
        if ($sender->EventArguments['FormPostValues']['Prefix'] == t('PrefixDiscussion.None', '-')) {
            $sender->EventArguments['FormPostValues']['Prefix'] = '';
        }
    }
}
