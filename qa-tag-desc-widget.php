<?php

require_once QA_PLUGIN_DIR.'q2a-tag-descriptions/similar-tag-db.php';

class qa_tag_descriptions_widget {

	function allow_template($template)
	{
		return ($template=='tag');
	}

	function allow_region($region)
	{
		return true;
	}

	function output_widget($region, $place, $themeobject, $template, $request, $qa_content)
	{
		require_once QA_INCLUDE_DIR.'qa-db-metas.php';

		$parts=explode('/', $request);
		$tag=$parts[1];

		$html = $this->get_tag_description($tag);
		$themeobject->output($html);
	}

	function option_default($option)
	{
		if ($option=='plugin_tag_desc_max_len')
			return 250;

		if ($option=='plugin_tag_desc_sidebar_html')
			return 1;
		if ($option=='plugin_tag_desc_enable_icon')
			return 1;
		if ($option=='plugin_tag_desc_icon_height')
			return 200;
		if ($option=='plugin_tag_desc_icon_width')
			return 624;
		if ($option=='plugin_tag_desc_permit_edit') {
			require_once QA_INCLUDE_DIR.'qa-app-options.php';
			return QA_PERMIT_EXPERTS;
		}

		return null;
	}

	function admin_form(&$qa_content)
	{
		require_once QA_INCLUDE_DIR.'qa-app-admin.php';
		require_once QA_INCLUDE_DIR.'qa-app-options.php';

		$permitoptions=qa_admin_permit_options(QA_PERMIT_USERS, QA_PERMIT_SUPERS, false, false);

		$saved=false;

		if (qa_clicked('plugin_tag_desc_save_button')) {
			qa_opt('plugin_tag_desc_max_len', (int)qa_post_text('plugin_tag_desc_ml_field'));
			qa_opt('plugin_tag_desc_permit_edit', (int)qa_post_text('plugin_tag_desc_pe_field'));
			qa_opt('plugin_tag_desc_enable_icon', (int)qa_post_text('plugin_tag_desc_enable_icon_field'));
			qa_opt('plugin_tag_desc_icon_height', (int)qa_post_text('plugin_tag_desc_icon_height_field'));
			qa_opt('plugin_tag_desc_icon_width', (int)qa_post_text('plugin_tag_desc_icon_width_field'));
			qa_opt('plugin_tag_desc_default_image', qa_post_text('plugin_tag_desc_default_image'));
			$saved=true;
		}
			qa_set_display_rules($qa_content, array(
				'plugin_tag_desc_icon_height' => 'plugin_tag_desc_enable_icon_field',
				'plugin_tag_desc_icon_width' => 'plugin_tag_desc_enable_icon_field',
			));
		return array(
			'ok' => $saved ? 'Tag descriptions settings saved' : null,

			'fields' => array(
				array(
					'label' => 'Maximum length of tooltips:',
					'type' => 'number',
					'value' => (int)qa_opt('plugin_tag_desc_max_len'),
					'suffix' => 'characters',
					'tags' => 'NAME="plugin_tag_desc_ml_field"',
				),
				array(
					'label' => 'Enable Images in tag links',
					'type' => 'checkbox',
					'value' => qa_opt('plugin_tag_desc_enable_icon'),
					'tags' => 'NAME="plugin_tag_desc_enable_icon_field" ID="plugin_tag_desc_enable_icon_field"',
				),
				array(
					'id' => 'plugin_tag_desc_icon_height',
					'label' => 'image height:',
					'suffix' => 'pixels',
					'type' => 'number',
					'value' => (int)qa_opt('plugin_tag_desc_icon_height'),
					'tags' => 'NAME="plugin_tag_desc_icon_height_field"',
				),
				array(
					'id' => 'plugin_tag_desc_icon_width',
					'label' => 'image width :',
					'suffix' => 'pixels',
					'type' => 'number',
					'value' => (int)qa_opt('plugin_tag_desc_icon_width'),
					'tags' => 'NAME="plugin_tag_desc_icon_width_field"',
				),
				array(
					'label' => 'Enable HTML in sidebar',
					'type' => 'checkbox',
					'value' => (int)qa_opt('plugin_tag_desc_sidebar_html'),
					'tags' => 'NAME="plugin_tag_desc_sidebar_html_field"',
				),
				array(
					'id' => 'plugin_tag_desc_default_image',
					'label' => 'Default image:',
					'type' => 'text',
					'value' => qa_opt('plugin_tag_desc_default_image'),
					'tags' => 'NAME="plugin_tag_desc_default_image"',
				),

				array(
					'label' => 'Allow editing:',
					'type' => 'select',
					'value' => @$permitoptions[qa_opt('plugin_tag_desc_permit_edit')],
					'options' => $permitoptions,
					'tags' => 'NAME="plugin_tag_desc_pe_field"',
				),
			),

			'buttons' => array(
				array(
					'label' => 'Save Changes',
					'tags' => 'NAME="plugin_tag_desc_save_button"',
				),
			),
		);
	}

	private function get_tag_description($tag)
	{
		$description=qa_db_tagmeta_get($tag, 'description');
		if (strlen($description)) {
			$path = QA_PLUGIN_DIR.'q2a-tag-descriptions/html/description_template.html';
			$template= file_get_contents($path);
			$params = $this->get_params($tag, $description);
			return strtr($template, $params);
		} elseif ($allowediting) {
			return '<A HREF="'.$editurlhtml.'">'.qa_lang_html('plugin_tag_desc/create_desc_link').'</A>';
		}

	}

	private function get_params($tag, $description)
	{
		$title=qa_db_tagmeta_get($tag, 'title');
		$headline=qa_db_tagmeta_get($tag, 'headline');
		$note=qa_db_tagmeta_get($tag, 'note');
		$imageurl=qa_db_tagmeta_get($tag, 'bg');
		$default_image=qa_opt('plugin_tag_desc_default_image');
		if (empty($imageurl)) {
			// デフォルト画像
			$imageurl = $default_image;
		}

		$similar_tag=$this->get_similar_tag($tag);
		$editurlhtml=qa_path_html('tag-edit/'.$tag);

		$allowediting=!qa_user_permit_error('plugin_tag_desc_permit_edit');
		if ($allowediting) {
			$editing = '<A HREF="'.$editurlhtml.'">'.qa_lang_html('plugin_tag_desc/edit').'</A>';
		} else {
			$editing = '';
		}
		return array(
			'^imageurl' => $imageurl,
			'^tag' => $tag,
			'^title' => $title,
			'^description' => $description,
			'^headline' => $headline,
			'^note' => $note,
			'^similar_tag' => $similar_tag,
			'^editing' => $editing,
			'^recent_title' => qa_lang_html('plugin_tag_desc/recent_title'),
			'^recent_date' => '2 日前',
		);

	}

	/*
	 * 関連するタグのHTMLを返す
	 */
	private function get_similar_tag($tag)
	{
		$stdb = new desc_similar_tag_db();
		$tagstring = $stdb->get_similar_tag_words($tag);
		if(!empty($tagstring)) {
			$tags = qa_tagstring_to_tags($tagstring);
			$path = QA_PLUGIN_DIR.'q2a-tag-descriptions/html/similar_tag_list.html';
			$template = file_get_contents($path); 
			$tag_list = $this->get_similar_tag_list($tags);
			$params = array(
				'^title' => qa_lang_html_sub('plugin_tag_desc/similar_tag_title', $tag),
				'^list' => $tag_list,
			);
			$html = strtr($template, $params);
			return $html;
		} else {
			return '';
		}
	}

	/*
	 * 関連するタグリストのHTMLを返す
	 */
	private function get_similar_tag_list($tags)
	{
		$html = '';
		if(!empty($tags)) {
			$path = QA_PLUGIN_DIR.'q2a-tag-descriptions/html/similar_tag_item.html';
			$template = file_get_contents($path);
			foreach ($tags as $tag) {
				$params = array(
					'^url' => qa_path_html('tag/'.$tag),
					'^tag' => $tag,
				);
				$html .= strtr($template, $params);
			}
		}
		return $html;
	}

}
