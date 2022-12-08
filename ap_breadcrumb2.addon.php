<?php

if ( !defined('RX_VERSION') )
{
	return;
}

/**
 * @file ap_breadcrumb2.addon.php
 * @author cydemo (cydemo@gmail.com)
 * @brief ap_breadcrumb2
 **/

 // Set print condition
if ( $called_position === 'before_display_content' && Context::getResponseMethod() === 'HTML' && Context::get('module') !== 'admin' )
{

	$selected_menu_srl = $addon_info->selected_menu_srl ?: 0;
	$module_info = Context::get('module_info');
	if ( !$selected_menu_srl )
	{
		$selected_menu_srl = $module_info->menu_srl;
	}
	$seperator = $addon_info->seperator ?: '>';
	$end = false;

	$oMenuAdminModel = getAdminModel('menu');
	$breadcrumb_info = $oMenuAdminModel->getMenuInfo((int)$selected_menu_srl);
	$breadcrumb_info->seperator = $seperator;
	$breadcrumb_info->show_homepage = ($addon_info->show_homepage === 'N') ? false: true;
	$breadcrumb_info->show_category = false;
	$breadcrumb_info->show_document = false;
	$breadcrumb_info->length = is_numeric($addon_info->length) ? $addon_info->length : false;


		function getCategoryInfo($mid, $category, $category_list, $show_upper_category = 'Y', $category_info = array())
		{
			$category_info[$category] = new stdClass();
			$category_info[$category]->href = getUrl('', 'mid', $mid, 'category', $category);
			$category_info[$category]->title = $category_list[$category]->title;

			if ( $category_list[$category]->depth && $show_upper_category !== 'N' )
			{
				return getCategoryInfo($mid, $category_list[$category]->parent_srl, $category_list, $show_upper_category, $category_info);
			}
			else
			{
				return array_reverse($category_info, true);
			}
		}

	if ( $addon_info->show_document !== 'N' && Context::get('document_srl') )
	{
		$breadcrumb_info->show_document = true;

		$document_srl = $end = Context::get('document_srl');
		$oDocument = DocumentModel::getDocument($document_srl);

		$breadcrumb_info->document_info = new stdClass();
		$breadcrumb_info->document_info->href = getUrl('', 'mid', $module_info->mid, 'document_srl', $document_srl);
		$breadcrumb_info->document_info->title = $oDocument->getTitle($addon_info->length);

		if ( $addon_info->show_category !== 'N' )
		{
			$category = $oDocument->get('category_srl');
			if ( $category )
			{
				$breadcrumb_info->show_category = true;

				$category_list = DocumentModel::getCategoryList($module_info->module_srl);
				$breadcrumb_info->category_info = getCategoryInfo($module_info->mid, $category, $category_list, $addon_info->show_upper_category);
			}
		}

	}

	if ( ($addon_info->show_category !== 'N' && !$breadcrumb_info->show_category) && Context::get('category') )
	{
		if ( !Context::get('document_srl') )
		{
			$breadcrumb_info->show_category = true;

			$category = $end = Context::get('category');
			$category_list = DocumentModel::getCategoryList($module_info->module_srl);
			$breadcrumb_info->category_info = getCategoryInfo($module_info->mid, $category, $category_list, $addon_info->show_upper_category);
		}
	}

	if ( !$end )
	{
		foreach ( $breadcrumb_info->list as $key1 => $val1 )
		{
			if ( $val1['selected'] )
			{
				if ( empty($val1['list']) )
				{
					$end = $key1;
					break;
				}
				else
				{
					foreach ( $val1['list'] as $key2 => $val2 )
					{
						if ( $val2['selected'] )
						{
							if ( empty($val2['list']) )
							{
								$end = $key2;
								break;
							}
							else
							{
								foreach ( $val2['list'] as $key3 => $val3 )
								{
									if ( $val3['selected'] )
									{
										if ( empty($val3['list']) )
										{
											$end = $key3;
											break;
										}
									}
								}
								if ( !$end )
								{
									$end = $key2;
									break;
								}
							}
						}
					}
					if ( !$end )
					{
						$end = $key1;
						break;
					}
				}
			}
		}
	}
	$breadcrumb_info->end = $end;

	Context::set('breadcrumb_info', $breadcrumb_info);

	// 템플릿 파일
	$oTemplate = TemplateHandler::getInstance();
	$tpl_file = 'breadcrumb.html';
	$tpl_skin = trim($addon_info->skin);
	$tpl_skin = file_exists('./addons/ap_breadcrumb2/skins/' . $tpl_skin . '/' . $tpl_file) ? $tpl_skin : 'default';
	debugPrint($tpl_skin);
	$tpl_path = './addons/ap_breadcrumb2/skins/' . $tpl_skin;
	$tpl = $oTemplate->compile($tpl_path, $tpl_file);

	$output = preg_replace('/<(?:\w+)[^>]+id=(?:"|\')content(?:"|\')(?:[^>]+)?>/is', '$0' . $tpl, $output);

}
/* !End of file */