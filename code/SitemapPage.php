<?php
/**
 * @package sitemap
 */
class SitemapPage extends Page {
	
	public static $db = array (
		'PagesToDisplay' => "Enum('All, ChildrenOf, Selected', 'All')"
	);
	
	public static $has_one = array (
		'ParentPage' => 'SiteTree'
	);
	
	public static $many_many = array (
		'PagesToShow' => 'SiteTree'
	);
	
	public static $icon = array('sitemap/images/sitemap', 'file');
	
	/**
	 * @return FieldSet
	 */
	public function getCMSFields() {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript('sitemap/javascript/SitemapPageAdmin.js');
		
		$fields = parent::getCMSFields();
		$fields->addFieldToTab (
			'Root.Content',
			$sitemapTab = new Tab('Sitemap',
				new HeaderField($this->fieldLabel('PagesToDisplay'), 2),
				new OptionSetField('PagesToDisplay', '', array (
					'All'        => $this->fieldLabel('AllPages'),
					'ChildrenOf' => $this->fieldLabel('ChildrenOf'),
					'Selected'   => $this->fieldLabel('Selected')
				)),
				new TreeDropdownField('ParentPageID', '', 'SiteTree'),
				new TreeMultiselectField('PagesToShow', '', 'SiteTree')
			),
			'Metadata'
		);
		$sitemapTab->setTitle($this->fieldLabel('Sitemap'));
		
		return $fields;
	}
	
	/**
	 * @return array
	 */
	public function fieldLabels() {
		return array_merge(parent::fieldLabels(), array (
			'Sitemap'        => _t('SitemapPage.SITEMAP', 'Sitemap'),
			'PagesToDisplay' => _t('SitemapPage.PAGESTOSHOW', 'Pages To Show In The Sitemap'),
			'AllPages'       => _t('SitemapPage.ALLPAGES', 'Display all pages which are displayed in the menu.'),
			'ChildrenOf'     => _t('SitemapPage.CHILDRENOF', 'Display the children of a specific page.'),
			'Selected'       => _t('SitemapPage.SELECTED', 'Display only the selected pages.')
		));
	}
	
	/**
	 * @return string
	 */
	public function getSitemap(DataObjectSet $set = null) {
		if(!$set) $set = $this->getRootPages();
		
		if($set && count($set)) {
			$sitemap = '<ul class="sitemap">';
			
			foreach($set as $page) {
				if($page->ShowInMenus && $page->ID != $this->ID && $page->canView()) {
					$sitemap .= sprintf (
						'<li><a href="%s" title="%s">%s</a>',
						$page->XML_val('Link'),
						$page->XML_val('MenuTitle'),
						$page->XML_val('Title')
					);
					
					if($children = $page->Children()) {
						$sitemap .= $this->getSitemap($children);
					}
					
					$sitemap .= '</li>';
				}
			}
			
			return $sitemap .'</ul>';
		}
	}
	
	/**
	 * @return DataObjectSet
	 */
	public function getRootPages() {
		switch($this->PagesToDisplay) {
			case 'All':
				return DataObject::get('SiteTree', '"ParentID" = 0 AND "ShowInMenus" = 1');
			case 'ChildrenOf':
				return DataObject::get('SiteTree', "\"ParentID\" = $this->ParentPageID AND \"ShowInMenus\" = 1");
			case 'Selected':
				return $this->PagesToShow('"ShowInMenus" = 1');
		}
	}
	
	/**
	 * Creates a default {@link SitemapPage} object if one does not currently exist.
	 */
	public function requireDefaultRecords() {
		if(!$sitemap = DataObject::get_one('SitemapPage')) {
			$sitemap = new SitemapPage();
			
			$sitemap->Title   = _t('SitemapPage.SITEMAP', 'Sitemap');
			$sitemap->Content = sprintf (
				'<p>%s</p>', 
				_t('SitemapPage.DEFAULTCONTENT','This page displays a sitemap of the pages in your site.')
			);
			
			$sitemap->write();
			$sitemap->doPublish();
			
			Database::alteration_message('Created default Sitemap page.', 'created');
		}
		
		parent::requireDefaultRecords();
	}
	
}

/**
 * @package sitemap
 */
class SitemapPage_Controller extends Page_Controller {
}