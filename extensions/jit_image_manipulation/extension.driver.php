<?php

Class extension_JIT_Image_Manipulation extends Extension
{

    const __OK__ = 100;
    const __ERROR_TRUSTED__ = 200;
    const __INVALID_RECIPES__ = 250;
    const __ERROR_SAVING_RECIPES__ = 300;

    public $recipes_errors = array();

    public function getSubscribedDelegates()
    {
        return array(
            array(
                'page' => '/system/preferences/',
                'delegate' => 'AddCustomPreferenceFieldsets',
                'callback' => 'appendPreferences'
            ),
            array(
                'page' => '/system/preferences/',
                'delegate' => 'Save',
                'callback' => '__SavePreferences'
            ),
            array(
                'page' => '/backend/',
                'delegate' => 'AppendPageAlert',
                'callback' => 'globalCacheWarning'
            )
        );
    }

    public function install()
    {
        require_once 'lib/class.htaccess.php';
        $htaccess = new HTAccess();
        try {
            if ($htaccess->exists()) {
                $htaccess->enableExtension();
            }
            // Create workspace directory
            General::realiseDirectory(WORKSPACE . '/jit-image-manipulation', Symphony::Configuration()->get('write_mode', 'directory'));
            // Now add Configuration values
            Symphony::Configuration()->set('cache', '1', 'image');
            Symphony::Configuration()->set('quality', '90', 'image');

            Symphony::Configuration()->write();
        } catch (Exception $ex) {
            $message = __(
                'An error occured while installing %s. %s',
                array(
                    __('JIT Image Manipulation'),
                    $ex->getMessage()
                )
            );
            throw new Exception($message);
        }
    }

    public function enable()
    {
        return $this->install();
    }

    public function update($previousVersion = false)
    {
        require_once 'lib/class.htaccess.php';
        $htaccess = new HTAccess();

        if (version_compare($previousVersion, '1.21', '<')) {
            try {
                // Simplify JIT htaccess rule [#75]
                if ($htaccess->exists()) {
                    $htaccess->simplifyJITAccessRule();
                }
            } catch (Exception $ex) {
                $message = __(
                    'An error occured while updating %s. %s',
                    array(
                        __('JIT Image Manipulation'),
                          $ex->getMessage()
                    )
                );
                throw new Exception($message);
            }
        }

        if (version_compare($previousVersion, '1.17', '<')) {
            // Add [B] flag to the .htaccess rule [#37]
            try {
                if ($htaccess->exists()) {
                    $htaccess->addBFlagToRule(
                    );
                }
            } catch (Exception $ex) {
                $message = __(
                    'An error occured while updating %s. %s',
                    array(
                        __('JIT Image Manipulation'),
                          $ex->getMessage()
                    )
                );
                throw new Exception($message);
            }
        }

        if (version_compare($previousVersion, '1.15', '<')) {
            // Move /manifest/jit-trusted-sites into /workspace/jit-image-manipulation
            if (General::realiseDirectory(WORKSPACE . '/jit-image-manipulation', Symphony::Configuration()->get('write_mode', 'directory')) && file_exists(MANIFEST . '/jit-trusted-sites')) {
                rename(MANIFEST . '/jit-trusted-sites', WORKSPACE . '/jit-image-manipulation/trusted-sites');
            }
        }
    }

    public function uninstall()
    {
        General::deleteDirectory(WORKSPACE . '/jit-image-manipulation');
        return $this->disable();
    }

    public function disable()
    {
        require_once 'lib/class.htaccess.php';
        $htaccess = new HTAccess();
        try {
            if ($htaccess->exists()) {
                $htaccess->disableExtension();
            }
        } catch (Exception $ex) {
            $message = __(
                'An error occured while disabling %s. %s',
                array(
                    __('JIT Image Manipulation'),
                      $ex->getMessage()
                )
            );
            throw new Exception($message);
        }
    }

    /*-------------------------------------------------------------------------
     *        Utilities:
     *-------------------------------------------------------------------------*/

    public function globalCacheWarning()
    {
        if (Symphony::Configuration()->get('cache', 'image') === '0') {
            if (Symphony::Author()->isDeveloper()) {
                Administration::instance()->Page->pageAlert(
                    __('The cache for images is disabled. For better performance, enable it in the %s.', [
                        '<a href="' . SYMPHONY_URL . '/system/preferences/">' . __('JIT preferences') . '</a>'
                    ]),
                    Alert::ERROR
                );
            } else {
                Administration::instance()->Page->pageAlert(
                    __('The cache for images is disabled. For better performance, ask your developer to enable it.'),
                    Alert::ERROR
                );
            }
        }
    }

    public function indent($depth = 1)
    {
        return str_repeat('    ', $depth);
    }

    public function trusted()
    {
        return is_readable(WORKSPACE . '/jit-image-manipulation/trusted-sites')
        ? file_get_contents(WORKSPACE . '/jit-image-manipulation/trusted-sites')
        : NULL;
    }

    public function saveTrusted($string)
    {
        $written = General::writeFile(WORKSPACE . '/jit-image-manipulation/trusted-sites', $string, Symphony::Configuration()->get('write_mode', 'file'));
        return ($written ? self::__OK__ : self::__ERROR_TRUSTED__);
    }

    /**
    * Validates all values of the given recipes.
    *
    * If no errors are detected, the recipes are written to the file
    * via the writeRecipes() method.
    *
    * @param array $recipes Array of recipes to validate and optionally save.
    * @return int Returns self::__INVALID_RECIPES__ if validation fails,
    *             self::__OK__ if saving succeeds,
    *             or self::__ERROR_SAVING_RECIPES__ on write failure.
    */
    public function saveRecipes($recipes)
    {
        if (is_array($recipes) && !empty($recipes)) {
            // array to collect recipe handles
            $handles = array();

            foreach ($recipes as $position => $recipe) {
                if (empty($recipe['name'])) {
                    $this->recipes_errors[$position]['missing'] = __('This is a required field.');
                }

                if (empty($recipe['url-parameter']) && $recipe['mode'] !== 'regex') {
                    $recipe['url-parameter'] = $_POST['jit_image_manipulation']['recipes'][$position]['url-parameter'] = Lang::createHandle($recipe['name']);
                }

                // check for recipes with same handles
                if (!in_array($recipe['url-parameter'], $handles)) {
                    // collect recipe handle
                    $handles[] = $recipe['url-parameter'];
                } elseif ($recipe['mode'] === 'regex') {
                    // regex already exists => set error
                    $this->recipes_errors[$position]['invalid'] = __('A recipe with this regular expression already exists.');
                } else {
                    // handle does exist => set error
                    $this->recipes_errors[$position]['invalid'] = __('A recipe with this handle already exists. All handles must be unique.');
                }

                // width and height not in regex and direct mode
                if ($recipe['mode'] !== 'regex' && $recipe['mode'] !== '0') {
                    // check for width
                    if (empty($recipe['width'])) {
                        $this->recipes_errors[$position]['width_invalid'] = __('%s is a required field.', array('Width'));
                    } else {
                        $width = trim($recipe['width']);
                        if (!is_numeric($width) || floor($width) != $width) {
                            $this->recipes_errors[$position]['width_invalid'] = __('Value must be a whole number.');
                        } else {
                            if ((int)$width < 1) {
                                $this->recipes_errors[$position]['width_invalid'] = __('Value must be greater than or equal to %s.', array('1'));
                            }
                        }
                    }

                    // check for height
                    if (empty($recipe['height'])) {
                        $this->recipes_errors[$position]['height_invalid'] = __('%s is a required field.', array('Height'));
                    } else {
                        $height = trim($recipe['height']);
                        if (!is_numeric($height) || floor($height) != $height) {
                            $this->recipes_errors[$position]['height_invalid'] = __('Value must be a whole number.');
                        } else {
                            if ((int)$height < 1) {
                                $this->recipes_errors[$position]['height_invalid'] = __('Value must be greater than or equal to %s.', array('1'));
                            }
                        }
                    }
                }

                // check for value of quality
                if (!empty($recipe['quality'])) {
                    $quality = trim($recipe['quality']);
                    if (!is_numeric($quality) || floor($quality) != $quality) {
                        $this->recipes_errors[$position]['quality_invalid'] = __('Value must be a whole number.');
                    } else {
                        if ((int)$quality > 100) {
                            $this->recipes_errors[$position]['quality_invalid'] = __('Value must be less than or equal to %s.', array('100'));
                        } elseif ((int)$quality < 0) {
                            $this->recipes_errors[$position]['quality_invalid'] = __('Value must be greater than or equal to %s.', array('0'));
                        }
                    }
                }

                if (isset($recipe['background'])) {
                    if (strlen($recipe['background']) > 0 && !preg_match('/^#[0-9a-fA-F]{6}$/', $recipe['background'])) {
                        $this->recipes_errors[$position]['background_invalid'] = __('‘%s’ must be a valid 6-digit hex color (e.g. #3fd558).', array('Background'));
                    }
                    $bg = ltrim($recipe['background'], '#');
                    $_POST['jit_image_manipulation']['recipes'][$position]['background'] = $bg;
                }

            }
        }

        // notify for recipe errors
        if (!empty($this->recipes_errors)) {
            return self::__INVALID_RECIPES__;
        } else {
            return $this->writeRecipes($recipes);
        }
    }

    private function writeRecipes($recipes)
    {
        $string = "<?php" . PHP_EOL;
        $string .= PHP_EOL ."\$recipes = array(" . PHP_EOL;

        if (is_array($recipes) && !empty($recipes)) {
            foreach ($recipes as $position => $recipe) {
                if (empty($recipe['url-parameter']) && $recipe['mode'] !== 'regex') {
                    $recipe['url-parameter'] = Lang::createHandle($recipe['name']);
                }

                if (isset($recipe['background'])) {
                    $bg = ltrim($recipe['background'], '#'); // removes leading hash
                    $recipe['background'] = $bg;
                }

                $string .= PHP_EOL . $this->indent(1) . "array(";
                foreach ($recipe as $key => $value){
                    $string .= PHP_EOL . $this->indent(2) . "'$key' => ".(strlen($value) > 0 ? "'".addslashes($value)."'" : 'NULL').",";
                }
                $string .= PHP_EOL . $this->indent(1) . "),";
                $string .= PHP_EOL . $this->indent(1) . "########" . PHP_EOL;
            }
        }

        $string .= PHP_EOL .");" . PHP_EOL;

        // try to write recipes file
        if (!General::writeFile(WORKSPACE . '/jit-image-manipulation/recipes.php', $string, Symphony::Configuration()->get('write_mode', 'file'))) {
            return self::__ERROR_SAVING_RECIPES__;
        } else {
            return self::__OK__;
        }
    }

    public function createRecipeDuplicatorTemplate($mode = '0', $position = '-1', $values = array(), $error = false)
    {
        $modes = array(
            '0' => __('Direct display'),
            '1' => __('Resize'),
            '2' => __('Crop to Fill'),
            '3' => __('Resize Canvas'),
            '4' => __('Resize to Fit'),
            'regex' => __('Custom')
        );

        $referencePositions = array(
            __('Top left'),
            __('Top center'),
            __('Top right'),
            __('Center left'),
            __('Center'),
            __('Center right'),
            __('Bottom left'),
            __('Bottom center'),
            __('Bottom right'),
        );
        $positionOptions = array();

        $defaults = array(
            'position' => null,
            'name' => null,
            'url-parameter' => null,
            'external' => null,
            'width' => null,
            'height' => null,
            'background' => null,
            'quality' => null,
            'jit-parameter' => null
        );

        $values = array_replace($defaults, (array)$values);

        $selected = isset($values['position']) ? $values['position'] : null;

        foreach ($referencePositions as $i => $p) {
            // Undefined array key 'position'
            // $positionOptions[] = array($i + 1, $i + 1 == $values['position'] ? true : false, $p);
            $positionOptions[] = array($i + 1, ($i + 1 == $selected), $p);
        }

        // general template settings
        $li = new XMLElement('li');
        $li->setAttribute('class', $position >= 0 ? 'instance expanded' : 'template');
        $li->setAttribute('data-type', 'mode-' . $mode);
        $header = new XMLElement('header', NULL, array('data-name' => $modes[$mode]));
        $label = (!empty($values['name'])) ? $values['name'] : __('New Recipe');
        $header->appendChild(new XMLElement('h4', '<strong>' . $label . '</strong> <span class="type">' . $modes[$mode] . '</span>'));
        $li->appendChild($header);
        $li->appendChild(Widget::Input("jit_image_manipulation[recipes][{$position}][mode]", General::sanitize($mode), 'hidden'));

        $group = new XMLElement('div');
        $group->setAttribute('class', 'two columns');

        // Name
        $label = Widget::Label(__('Name'), null, 'column');
        $input = Widget::Input("jit_image_manipulation[recipes][{$position}][name]", General::sanitize($values['name']));
        $input->setAttribute('required', 'required');
        $label->appendChild($input);
        if (is_array($error) && isset($error['missing'])) {
            $group->appendChild(Widget::Error($label, $error['missing']));
        } else {
            $group->appendChild($label);
        }

        // Handle
        $url_param = General::sanitize($values['url-parameter']);
        $label_hint = '';
        if (!empty($url_param) && !is_array($error)) {
            if ($values['external'] === '1') {
                $label_hint = 'image/' . $url_param . '/example.net/path/to/external/image.jpg';
            } else {
                $label_hint = 'image/' . $url_param . '/path/to/local/image.jpg';
            }
        }

        $label_text = $mode === 'regex' ? __('Regular Expression') : __('Handle') . ' <i><strong>' . $label_hint . '</strong></i>';
        $label = Widget::Label(__($label_text), null, 'column');
        $label->appendChild(Widget::Input("jit_image_manipulation[recipes][{$position}][url-parameter]", General::sanitize($values['url-parameter'])));
        if (is_array($error) && isset($error['invalid'])) {
            $group->appendChild(Widget::Error($label, $error['invalid']));
        } else {
            $group->appendChild($label);
        }

        $li->appendChild($group);

        // width and height for modes 1, 2, 3 and 4
        if ($mode === '1' || $mode === '2' || $mode === '3' || $mode === '4') {
            $attributes = array(
                'min' => '1',
                'step' => '1',
                'required' => 'required'
            );
            $group = new XMLElement('div');
            $group->setAttribute('class', 'two columns');
            $label = Widget::Label(__('Width'), null, 'column');
            $input = Widget::Input("jit_image_manipulation[recipes][{$position}][width]", General::sanitize($values['width']), 'number', $attributes);
            $label->appendChild($input);
            if (is_array($error) && isset($error['width_invalid'])) {
                $group->appendChild(Widget::Error($label, $error['width_invalid']));
            } else {
                $group->appendChild($label);
            }

            $label = Widget::Label(__('Height'), null, 'column');
            $input = Widget::Input("jit_image_manipulation[recipes][{$position}][height]", General::sanitize($values['height']), 'number', $attributes);
            $label->appendChild($input);
            if (is_array($error) && isset($error['height_invalid'])) {
                $group->appendChild(Widget::Error($label, $error['height_invalid']));
            } else {
                $group->appendChild($label);
            }

            $li->appendChild($group);
        }

        // position and background for mode 2 and 3
        if ($mode === '2' || $mode === '3') {
            $group = new XMLElement('div');
            $group->setAttribute('class', 'two columns');
            $label = Widget::Label(__('Position'), null, 'column');
            $label->appendChild(Widget::Select("jit_image_manipulation[recipes][{$position}][position]", $positionOptions));
            $group->appendChild($label);
            $label = Widget::Label(__('Background Color'), null, 'column');
            $label->appendChild(new XMLElement('i', __('Optional')));
            $input = Widget::Input("jit_image_manipulation[recipes][{$position}][background]", null, 'color');
            if ($values['background'] !== null) {
                $input->setAttribute('value', '#' . $values['background']); // add leading hash
            }
            $label->appendChild($input);
            if (is_array($error) && isset($error['background_invalid'])) {
                $group->appendChild(Widget::Error($label, $error['background_invalid']));
            } else {
                $group->appendChild($label);
            }
            $li->appendChild($group);
        }

        // regex mode
        if ($mode === 'regex') {
            $label = Widget::Label(__('JIT Parameter'));
            $label->appendChild(Widget::Input("jit_image_manipulation[recipes][{$position}][jit-parameter]", General::sanitize($values['jit-parameter'])));
            $li->appendChild($label);
        }

        // more general settings, except quality for direct display and external image for regex mode
        $group = new XMLElement('div');
        $group->setAttribute('class', 'two columns');
        if ($mode !== '0') {
            $attributes = array(
                'min'  => '0',
                'max'  => '100',
                'step' => '1'
            );
            $label = Widget::Label(__('Image quality'), null, 'column');
            $label->appendChild(new XMLElement('i', __('Optional')));
            $input = Widget::Input("jit_image_manipulation[recipes][{$position}][quality]", General::sanitize($values['quality']), 'number', $attributes);
            $label->appendChild($input);
            if (is_array($error) && isset($error['quality_invalid'])) {
                $group->appendChild(Widget::Error($label, $error['quality_invalid']));
            } else {
                $group->appendChild($label);
            }
        }
        if ($mode !== 'regex') {
            $label = Widget::Label();
            $label->setAttribute('class', 'column justified');
            $hidden = Widget::Input("jit_image_manipulation[recipes][{$position}][external]", '0', 'hidden');
            $input = Widget::Input("jit_image_manipulation[recipes][{$position}][external]", '1', 'checkbox');
            if ($values['external'] == '1') $input->setAttribute('checked', 'checked');
            $label->setValue($input->generate() . ' ' . __('External Image'));
            $group->appendChild($hidden);
            $group->appendChild($label);
        }

        $li->appendChild($group);

        return $li;
    }

    /*-------------------------------------------------------------------------
     *        Delegates:
     *-------------------------------------------------------------------------*/

    public function appendPreferences($context)
    {
        // Check if JIT configuration folder exists
        if (!file_exists(WORKSPACE . '/jit-image-manipulation/')) {
            Administration::instance()->Page->pageAlert(__('The JIT configuration folder, %s, does not exist. You will not be able to save recipes and trusted sites.', array('<code>/workspace/jit-image-manipulation/</code>')), Alert::ERROR);
        }

        // Alert messages for JIT configuration errors
        $errors = $context['errors'];
        if (isset($errors['jit-trusted-sites'])) {
            Administration::instance()->Page->pageAlert($errors['jit-trusted-sites'], Alert::ERROR);
        }
        if (isset($errors['jit-recipes'])) {
            Administration::instance()->Page->pageAlert($errors['jit-recipes'], Alert::ERROR);
        }

        // JavaScript for recipes duplicator
        Administration::instance()->Page->addScriptToHead(URL . '/extensions/jit_image_manipulation/assets/jit_image_manipulation.preferences.js', 3134);

        $group = new XMLElement('fieldset');
        $group->setAttribute('class', 'settings');
        $group->appendChild(new XMLElement('legend', __('JIT Image Manipulation')));
        $group->appendChild(
            new XMLElement('p', __('Recipes are named rules for the JIT settings.'), array('class' => 'help'))
        );

        // recipes duplicator
        $group->appendChild(new XMLElement('p', __('Recipes'), array('class' => 'label')));
        $div = new XMLElement('div', null, array('class' => 'frame jit-duplicator'));
        $duplicator = new XMLElement('ol');
        $duplicator->setAttribute('data-add', __('Add recipe'));
        $duplicator->setAttribute('data-remove', __('Remove recipe'));

        $duplicator->appendChild(self::createRecipeDuplicatorTemplate('0'));
        $duplicator->appendChild(self::createRecipeDuplicatorTemplate('1'));
        $duplicator->appendChild(self::createRecipeDuplicatorTemplate('2'));
        $duplicator->appendChild(self::createRecipeDuplicatorTemplate('3'));
        $duplicator->appendChild(self::createRecipeDuplicatorTemplate('4'));
        $duplicator->appendChild(self::createRecipeDuplicatorTemplate('regex'));

        // use recipes POST datain case of an error
        $post_recipes = (isset($_POST['jit_image_manipulation']['recipes'])) ? $_POST['jit_image_manipulation']['recipes'] : array();

        if (!empty($post_recipes) && !empty($this->recipes_errors)) {
            foreach ($post_recipes as $position => $recipe) {
                $recipeError = isset($this->recipes_errors[$position]) ? $this->recipes_errors[$position] : null;
                $duplicator->appendChild(
                    self::createRecipeDuplicatorTemplate($recipe['mode'], $position, $recipe, $recipeError)
                );
            }
        }
        // otherwise use saved recipes data
        else {
            (file_exists(WORKSPACE . '/jit-image-manipulation/recipes.php')) ? include(WORKSPACE . '/jit-image-manipulation/recipes.php') : $recipes = array();
            if (is_array($recipes) && !empty($recipes)) {
                foreach ($recipes as $position => $recipe) {
                    $duplicator->appendChild(
                        self::createRecipeDuplicatorTemplate($recipe['mode'], $position, $recipe)
                    );
                }
            }
        }

        $div->appendChild($duplicator);
        $group->appendChild($div);

        // checkbox to disable chache
        $label = Widget::Label();
        $input = Widget::Input('settings[image][cache]', '1', 'checkbox');
        if (Symphony::Configuration()->get('cache', 'image') === '1') {
            $input->setAttribute('checked', 'checked');
        }
        $label->setValue($input->generate() . ' ' . __('Enable cache for images (recommend)'));
        $span = new XMLElement('span', __('When disabled, images are regenerated each time they are called up (slower, but helpful for debugging or development).'), array('class' => 'help'));
        $label->appendChild($span);
        $group->appendChild($label);

        // text input for image quality
        $attributes = array(
            'min' => '0',
            'max' => '100',
            'step' => '1',
            'required' => 'required',
        );
        $label = Widget::Label(__('Image quality '));
        $input = Widget::Input('settings[image][quality]', General::sanitize(Symphony::Configuration()->get('quality', 'image')), 'number', $attributes);
        if (isset($context['errors']['quality'])) {
            $input->setAttribute('value', '90');
        }
        $label->appendChild($input);
        if (isset($context['errors']['quality'])) {
            $group->appendChild(Widget::Error($label, $context['errors']['quality']));
        } else {
            $group->appendChild($label);
        }

        // checkbox to disable regular rules
        $label = Widget::Label();
        $input = Widget::Input('settings[image][disable_regular_rules]', 'yes', 'checkbox');
        if (Symphony::Configuration()->get('disable_regular_rules', 'image') === 'yes') {
            $input->setAttribute('checked', 'checked');
        }
        $label->setValue($input->generate() . ' ' . __('Disable dynamic URLs and use named recipes only'));
        $group->appendChild($label);

        // checkbox to disable up-scaling
        $label = Widget::Label();
        $input = Widget::Input('settings[image][disable_upscaling]', 'yes', 'checkbox');
        if (Symphony::Configuration()->get('disable_upscaling', 'image') === 'yes') {
            $input->setAttribute('checked', 'checked');
        }
        $label->setValue($input->generate() . ' ' . __('Disable upscaling of images beyond the original size'));
        $group->appendChild($label);

        // checkbox to diable proxy transformation of images
        $label = Widget::Label();
        $input = Widget::Input('settings[image][disable_proxy_transform]', 'yes', 'checkbox');
        if (Symphony::Configuration()->get('disable_proxy_transform', 'image') === 'yes') {
            $input->setAttribute('checked', 'checked');
        }
        $label->setValue($input->generate() . ' ' . __('Prevent ISP proxy transformation'));
        $group->appendChild($label);

        // text input to allow external request origins
        $label = Widget::Label(__('Add Cross-Origin Header'));
        $input = Widget::Input('settings[image][allow_origin]', General::sanitize(Symphony::Configuration()->get('allow_origin', 'image')));
        $label->appendChild($input);
        $group->appendChild($label);

        // textarea for trusted sites
        $label = Widget::Label(__('Trusted Sites'));
        $label->appendChild(Widget::Textarea('jit_image_manipulation[trusted_external_sites]', 5, 50, General::sanitize($this->trusted())));

        $group->appendChild($label);
        $group->appendChild(new XMLElement('p', __('Leave empty to disable external linking. Single rule per line. Add * at end for wild card matching.'), array('class' => 'help')));

        $context['wrapper']->appendChild($group);
    }

    public function __SavePreferences($context)
    {
        $recipes_saved = self::__OK__;

        if (!isset($context['settings']['image']['cache'])) {
            $context['settings']['image']['cache'] = '0';
        }

        if (isset($context['settings']['image']['quality'])) {
            $quality = trim($context['settings']['image']['quality']);
            if ($quality === '') {
                $context['errors']['quality'] = __('%s is a required field.', array('Quality'));
            } else {
                if (is_numeric($quality) === false) {
                    $context['errors']['quality'] = __('Value must be a number.');
                } else {
                    if ((int)$quality > 100) {
                        $context['errors']['quality'] = __('Value must be less than or equal to %s.', array('100'));
                    } elseif ((int)$quality < 0) {
                        $context['errors']['quality'] = __('Value must be greater than or equal to %s.', array('0'));
                    }
                }
            }
        }

        if (!isset($context['settings']['image']['disable_regular_rules'])) {
            $context['settings']['image']['disable_regular_rules'] = 'no';
        }

        if (!isset($context['settings']['image']['disable_upscaling'])) {
            $context['settings']['image']['disable_upscaling'] = 'no';
        }

        if (!isset($context['settings']['image']['disable_proxy_transform'])) {
            $context['settings']['image']['disable_proxy_transform'] = 'no';
        }

        if (!isset($context['settings']['image']['allow_origin'])) {
            $context['settings']['image']['allow_origin'] = '"null"';
        }

        // save trusted sites
        $trusted_saved = $this->saveTrusted(stripslashes($_POST['jit_image_manipulation']['trusted_external_sites']));
        // there were errors saving the trusted files
        if ($trusted_saved == self::__ERROR_TRUSTED__) {
            $context['errors']['jit-trusted-sites'] = __('An error occurred while saving the JIT trusted sites. Make sure the trusted sites file, %s, exists and is writable.', array('<code>/workspace/jit-image-manipulation/trusted-sites</code>'));
        };

        // save recipes (if they exist)
        if (isset($_POST['jit_image_manipulation']['recipes'])) {
            $recipes_saved = $this->saveRecipes($_POST['jit_image_manipulation']['recipes']);
        }
        // nothing posted, so clear recipes
        else {
            $recipes_saved = $this->saveRecipes(array());
        }

        // there were errors saving the recipes
        if ($recipes_saved == self::__ERROR_SAVING_RECIPES__) {
            $context['errors']['jit-recipes'] = __('An error occurred while saving the JIT recipes. Make sure the recipes file, %s, exists and is writable.', array('<code>/workspace/jit-image-manipulation/recipes.php</code>'));
        }

        // there were duplicate recipes handles
        if ($recipes_saved == self::__INVALID_RECIPES__) {
            $context['errors']['jit-recipes'] = __('An error occurred while saving the JIT recipes.');
        }
    }

}
