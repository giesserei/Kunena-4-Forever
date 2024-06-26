<?php
/**
 * Kunena Component
 * @package Kunena.Framework
 * @subpackage Pagination
 *
 * @copyright (C) 2008 - 2016 Kunena Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link https://www.kunena.org
 **/
defined ( '_JEXEC' ) or die ();

/**
 * Pagination Class. Provides a common interface for content pagination for the Joomla! CMS.
 *
 * @package     Joomla.Libraries
 * @subpackage  Pagination
 * @since       1.5
 */
class KunenaPagination
{
	/**
	 * @var    integer  The record number to start displaying from.
	 * @since  1.5
	 */
	public $limitstart = null;

	/**
	 * @var    integer  Number of rows to display per page.
	 * @since  1.5
	 */
	public $limit = null;

	/**
	 * @var    integer  Total number of rows.
	 * @since  1.5
	 */
	public $total = null;

	/**
	 * @var    integer  Prefix used for request variables.
	 * @since  1.6
	 */
	public $prefix = null;

	/**
	 * @var    integer  Value pagination object begins at
	 * @since  3.0
	 */
	public $pagesStart;

	/**
	 * @var    integer  Value pagination object ends at
	 * @since  3.0
	 */
	public $pagesStop;

	/**
	 * @var    integer  Current page
	 * @since  3.0
	 */
	public $pagesCurrent;

	/**
	 * @var    integer  Total number of pages
	 * @since  3.0
	 */
	public $pagesTotal;

	/**
	 * @var    boolean  View all flag
	 * @since  3.0
	 */
	protected $viewall = false;

	/**
	 * @var    integer
	 */
	public $stickyStart = null;

	/**
	 * @var    integer
	 */
	public $stickyStop = null;

	/**
	 * @var    JUri
	 */
	public $uri = null;

	protected $itemActiveChrome = null;
	protected $itemInactiveChrome = null;
	protected $listChrome = null;
	protected $footerChrome = null;

	/**
	 * Additional URL parameters to be added to the pagination URLs generated by the class.  These
	 * may be useful for filters and extra values when dealing with lists and GET requests.
	 *
	 * @var    array
	 * @since  3.0
	 */
	protected $additionalUrlParams = array();

	/**
	 * Constructor.
	 *
	 * @param   integer  $total       The total number of items.
	 * @param   integer  $limitstart  The offset of the item to start at.
	 * @param   integer  $limit       The number of items to display per page.
	 * @param   string   $prefix      The prefix used for request variables.
	 *
	 * @since   1.5
	 */
	public function __construct($total, $limitstart, $limit, $prefix = '')
	{
		// Workaround for J!2.5:
		jimport('joomla.html.pagination');
		class_exists('JPagination');

		// Value/type checking.
		$this->total = (int) $total;
		$this->limitstart = (int) max($limitstart, 0);
		$this->limit = (int) max($limit, 0);
		$this->prefix = $prefix;

		if ($this->limit > $this->total)
		{
			$this->limitstart = 0;
		}

		if (!$this->limit)
		{
			$this->limit = $total;
			$this->limitstart = 0;
		}

		/*
		 * If limitstart is greater than total (i.e. we are asked to display records that don't exist)
		 * then set limitstart to display the last natural page of results
		 */
		if ($this->limitstart > $this->total - $this->limit)
		{
			$this->limitstart = max(0, (int) (ceil($this->total / $this->limit) - 1) * $this->limit);
		}

		// Set the total pages and current page values.
		if ($this->limit > 0)
		{
			$this->pagesTotal = ceil($this->total / $this->limit);
			$this->pagesCurrent = ceil(($this->limitstart + 1) / $this->limit);
		}

		$this->setDisplayedPages();

		// If we are viewing all records set the view all flag to true.
		if ($limit == 0)
		{
			$this->viewall = true;
		}
	}

	/**
	 * Set URI for pagination.
	 *
	 * @param  Juri  $uri  JUri object.
	 *
	 * @return  KunenaPagination  Method supports chaining.
	 */
	public function setUri(JUri $uri)
	{
		$this->uri = clone $uri;

		return $this;
	}

	/**
	 * Set number of displayed pages.
	 *
	 * @param  int  $displayed  Number of displayed pages.
	 * @param  int  $start  How many items to display from the beginning (1 2 ...)
	 * @param  int  $end  How many items to display from the end (... 49 50)
	 *
	 * @return  KunenaPagination  Method supports chaining.
	 */
	public function setDisplayedPages($displayed = 10, $start = 0, $end = 0)
	{
		$this->stickyStart = $start;
		$this->stickyStop = $end;

		// Set the pagination iteration loop values.
		$this->pagesStart = $this->pagesCurrent - (int) ($displayed / 2);

		if ($this->pagesStart < 1 + $start)
		{
			$this->pagesStart = 1 + $start;
		}

		if ($this->pagesStart + $displayed - $start > $this->pagesTotal)
		{
			$this->pagesStop = $this->pagesTotal;

			if ($this->pagesTotal < $displayed)
			{
				$this->pagesStart = 1 + $start;
			}
			else
			{
				$this->pagesStart = $this->pagesTotal - $displayed + 1 + $start;
			}
		}
		else
		{
			$this->pagesStop = $this->pagesStart + $displayed - 1 - $end;
		}

		$this->pagesStop = max(1, $this->pagesStop);
		$this->pagesTotal = max(1, $this->pagesTotal);

		return $this;
	}

	/**
	 * Method to set an additional URL parameter to be added to all pagination class generated
	 * links.
	 *
	 * @param   string  $key    The name of the URL parameter for which to set a value.
	 * @param   mixed   $value  The value to set for the URL parameter.
	 *
	 * @return  mixed  The old value for the parameter.
	 *
	 * @since   1.6
	 */
	public function setAdditionalUrlParam($key, $value)
	{
		// Get the old value to return and set the new one for the URL parameter.
		$result = isset($this->additionalUrlParams[$key]) ? $this->additionalUrlParams[$key] : null;

		// If the passed parameter value is null unset the parameter, otherwise set it to the given value.
		if ($value === null)
		{
			unset($this->additionalUrlParams[$key]);
		}
		else
		{
			$this->additionalUrlParams[$key] = $value;
		}

		return $result;
	}

	/**
	 * Method to get an additional URL parameter (if it exists) to be added to
	 * all pagination class generated links.
	 *
	 * @param   string  $key  The name of the URL parameter for which to get the value.
	 *
	 * @return  mixed  The value if it exists or null if it does not.
	 *
	 * @since   1.6
	 */
	public function getAdditionalUrlParam($key)
	{
		$result = isset($this->additionalUrlParams[$key]) ? $this->additionalUrlParams[$key] : null;

		return $result;
	}

	/**
	 * Return the rationalised offset for a row with a given index.
	 *
	 * @param   integer  $index  The row index
	 *
	 * @return  integer  Rationalised offset for a row with a given index.
	 *
	 * @since   1.5
	 */
	public function getRowOffset($index)
	{
		return $index + 1 + $this->limitstart;
	}

	/**
	 * Return the pagination data object, only creating it if it doesn't already exist.
	 *
	 * @return  object   Pagination data object.
	 *
	 * @since   1.5
	 */
	public function getData()
	{
		// Do not have static cache here (if needed, keep it in object context).
		$data = $this->_buildDataObject();
		return $data;
	}

	protected function setChrome()
	{
		$template = KunenaFactory::getTemplate();
		$this->itemActiveChrome = array($template, 'getPaginationItemActive');
		$this->itemInactiveChrome = array($template, 'getPaginationItemInactive');
		$this->listChrome = array($template, 'getPaginationListRender');
		$this->footerChrome = array($template, 'getPaginationListFooter');
	}

	/**
	 * Create and return the pagination pages counter string, ie. Page 2 of 4.
	 *
	 * @return  string   Pagination pages counter string.
	 *
	 * @since   1.5
	 */
	public function getPagesCounter()
	{
		$html = null;

		if ($this->pagesTotal > 1)
		{
			$html .= JText::sprintf('JLIB_HTML_PAGE_CURRENT_OF_TOTAL', $this->pagesCurrent, $this->pagesTotal);
		}

		return $html;
	}

	/**
	 * Create and return the pagination result set counter string, e.g. Results 1-10 of 42
	 *
	 * @return  string   Pagination result set counter string.
	 *
	 * @since   1.5
	 */
	public function getResultsCounter()
	{
		$html = null;
		$fromResult = $this->limitstart + 1;

		// If the limit is reached before the end of the list.
		if ($this->limitstart + $this->limit < $this->total)
		{
			$toResult = $this->limitstart + $this->limit;
		}
		else
		{
			$toResult = $this->total;
		}

		// If there are results found.
		if ($this->total > 0)
		{
			$msg = JText::sprintf('JLIB_HTML_RESULTS_OF', $fromResult, $toResult, $this->total);
			$html .= "\n" . $msg;
		}
		else
		{
			$html .= "\n" . JText::_('JLIB_HTML_NO_RECORDS_FOUND');
		}

		return $html;
	}

	/**
	 * Create and return the pagination page list string, ie. Previous, Next, 1 2 3 ... x.
	 *
	 * @return  string  Pagination page list string.
	 *
	 * @since   1.5
	 */
	public function getPagesLinks()
	{
		if ($this->total <= $this->limit)
		{
			return '';
		}

		// Build the page navigation list.
		$data = $this->_buildDataObject();

		$list = array();
		$list['prefix'] = $this->prefix;

		$this->setChrome();

		// Build the select list
		if ($data->all->base !== null)
		{
			$list['all']['active'] = true;
			$list['all']['data'] = call_user_func($this->itemActiveChrome, $data->all);
		}
		else
		{
			$list['all']['active'] = false;
			$list['all']['data'] = call_user_func($this->itemInactiveChrome, $data->all);
		}

		if ($data->start->base !== null)
		{
			$list['start']['active'] = true;
			$list['start']['data'] = call_user_func($this->itemActiveChrome, $data->start);
		}
		else
		{
			$list['start']['active'] = false;
			$list['start']['data'] = call_user_func($this->itemInactiveChrome, $data->start);
		}

		if ($data->previous->base !== null)
		{
			$list['previous']['active'] = true;
			$list['previous']['data'] = call_user_func($this->itemActiveChrome, $data->previous);
		}
		else
		{
			$list['previous']['active'] = false;
			$list['previous']['data'] = call_user_func($this->itemInactiveChrome, $data->previous);
		}

		// Make sure it exists
		$list['pages'] = array();

		foreach ($data->pages as $i => $page)
		{
			if ($page->base !== null)
			{
				$list['pages'][$i]['active'] = true;
				$list['pages'][$i]['data'] = call_user_func($this->itemActiveChrome, $page);
			}
			else
			{
				$list['pages'][$i]['active'] = false;
				$list['pages'][$i]['data'] = call_user_func($this->itemInactiveChrome, $page);
			}
		}

		if ($data->next->base !== null)
		{
			$list['next']['active'] = true;
			$list['next']['data'] = call_user_func($this->itemActiveChrome, $data->next);
		}
		else
		{
			$list['next']['active'] = false;
			$list['next']['data'] = call_user_func($this->itemInactiveChrome, $data->next);
		}

		if ($data->end->base !== null)
		{
			$list['end']['active'] = true;
			$list['end']['data'] = call_user_func($this->itemActiveChrome, $data->end);
		}
		else
		{
			$list['end']['active'] = false;
			$list['end']['data'] = call_user_func($this->itemInactiveChrome, $data->end);
		}

		return call_user_func($this->listChrome, $list);
	}

	/**
	 * Return the pagination footer.
	 *
	 * @return  string  Pagination footer.
	 *
	 * @since   1.5
	 */
	public function getListFooter()
	{
		$list = array();
		$list['prefix'] = $this->prefix;
		$list['limit'] = $this->limit;
		$list['limitstart'] = $this->limitstart;
		$list['total'] = $this->total;
		$list['limitfield'] = $this->getLimitBox();
		$list['pagescounter'] = $this->getPagesCounter();
		$list['pageslinks'] = $this->getPagesLinks();

		$this->setChrome();

		return call_user_func($this->footerChrome, $list);
	}

	/**
	 * Creates a dropdown box for selecting how many records to show per page.
	 *
	 * @param   bool    $all  True if you want to display option for all.
	 *
	 * @return  string  The HTML for the limit # input box.
	 *
	 * @since   1.5
	 */
	public function getLimitBox($all = false)
	{
		$app = JFactory::getApplication();
		$limits = array();

		// Make the option list.
		for ($i = 5; $i <= 30; $i += 5)
		{
			$limits[] = JHtml::_('select.option', "$i");
		}

		$limits[] = JHtml::_('select.option', '50', JText::_('J50'));
		$limits[] = JHtml::_('select.option', '100', JText::_('J100'));

		if ($all)
		{
			$limits[] = JHtml::_('select.option', '0', JText::_('JALL'));
		}

		$selected = $this->viewall ? 0 : $this->limit;

		// Build the select list.
                if ($app->isClient('administrator'))
		{
			$html = JHtml::_(
				'select.genericlist',
				$limits,
				$this->prefix . 'limit',
				'class="inputbox input-mini" size="1" onchange="Joomla.submitform();"',
				'value',
				'text',
				$selected
			);
		}
		else
		{
			$html = JHtml::_(
				'select.genericlist',
				$limits,
				$this->prefix . 'limit',
				'class="inputbox input-mini" size="1" onchange="this.form.submit()"',
				'value',
				'text',
				$selected
			);
		}

		return $html;
	}

	/**
	 * Return the icon to move an item UP.
	 *
	 * @param   integer  $i          The row index.
	 * @param   boolean  $condition  True to show the icon.
	 * @param   string   $task       The task to fire.
	 * @param   string   $alt        The image alternative text string.
	 * @param   boolean  $enabled    An optional setting for access control on the action.
	 * @param   string   $checkbox   An optional prefix for checkboxes.
	 *
	 * @return  string   Either the icon to move an item up or a space.
	 *
	 * @since   1.5
	 */
	public function orderUpIcon($i, $condition = true, $task = 'orderup', $alt = 'JLIB_HTML_MOVE_UP', $enabled = true, $checkbox = 'cb')
	{
		if (($i > 0 || ($i + $this->limitstart > 0)) && $condition)
		{
			return JHtml::_('jgrid.orderUp', $i, $task, '', $alt, $enabled, $checkbox);
		}
		else
		{
			return '&#160;';
		}
	}

	/**
	 * Return the icon to move an item DOWN.
	 *
	 * @param   integer  $i          The row index.
	 * @param   integer  $n          The number of items in the list.
	 * @param   boolean  $condition  True to show the icon.
	 * @param   string   $task       The task to fire.
	 * @param   string   $alt        The image alternative text string.
	 * @param   boolean  $enabled    An optional setting for access control on the action.
	 * @param   string   $checkbox   An optional prefix for checkboxes.
	 *
	 * @return  string   Either the icon to move an item down or a space.
	 *
	 * @since   1.5
	 */
	public function orderDownIcon($i, $n, $condition = true, $task = 'orderdown', $alt = 'JLIB_HTML_MOVE_DOWN', $enabled = true, $checkbox = 'cb')
	{
		if (($i < $n - 1 || $i + $this->limitstart < $this->total - 1) && $condition)
		{
			return JHtml::_('jgrid.orderDown', $i, $task, '', $alt, $enabled, $checkbox);
		}
		else
		{
			return '&#160;';
		}
	}

	/**
	 * Create the HTML for a list footer
	 *
	 * @param   array  $list  Pagination list data structure.
	 *
	 * @return  string  HTML for a list footer
	 *
	 * @since   1.5
	 */
	protected function _list_footer($list)
	{
		$html = "<div class=\"list-footer\">\n";

		$html .= "\n<div class=\"limit\">" . JText::_('JGLOBAL_DISPLAY_NUM') . $list['limitfield'] . "</div>";
		$html .= $list['pageslinks'];
		$html .= "\n<div class=\"counter\">" . $list['pagescounter'] . "</div>";

		$html .= "\n<input type=\"hidden\" name=\"" . $list['prefix'] . "limitstart\" value=\"" . $list['limitstart'] . "\" />";
		$html .= "\n</div>";

		return $html;
	}

	/**
	 * Create the html for a list footer
	 *
	 * @param   array  $list  Pagination list data structure.
	 *
	 * @return  string  HTML for a list start, previous, next,end
	 *
	 * @since   1.5
	 */
	protected function _list_render($list)
	{
		// Reverse output rendering for right-to-left display.
		$html = '<ul>';
		$html .= '<li class="pagination-start">' . $list['start']['data'] . '</li>';
		$html .= '<li class="pagination-prev">' . $list['previous']['data'] . '</li>';

		foreach ($list['pages'] as $page)
		{
			$html .= '<li>' . $page['data'] . '</li>';
		}

		$html .= '<li class="pagination-next">' . $list['next']['data'] . '</li>';
		$html .= '<li class="pagination-end">' . $list['end']['data'] . '</li>';
		$html .= '</ul>';

		return $html;
	}

	/**
	 * Method to create an active pagination link to the item
	 *
	 * @param   JPaginationObject  $item  The object with which to make an active link.
	 *
	 * @return  string  HTML link
	 *
	 * @since   1.5
	 */
	protected function _item_active(JPaginationObject $item)
	{
		$app = JFactory::getApplication();
                if ($app->isClient('administrator'))
		{
			if ($item->base > 0)
			{
				return "<a title=\"" . $item->text . "\" onclick=\"document.adminForm." . $this->prefix . "limitstart.value=" . $item->base
					. "; Joomla.submitform();return false;\">" . $item->text . "</a>";
			}
			else
			{
				return "<a title=\"" . $item->text . "\" onclick=\"document.adminForm." . $this->prefix
					. "limitstart.value=0; Joomla.submitform();return false;\">" . $item->text . "</a>";
			}
		}
		else
		{
			return "<a title=\"" . $item->text . "\" href=\"" . $item->link . "\" class=\"pagenav\">" . $item->text . "</a>";
		}
	}

	/**
	 * Method to create an inactive pagination string
	 *
	 * @param   JPaginationObject  $item  The item to be processed
	 *
	 * @return  string
	 *
	 * @since   1.5
	 */
	protected function _item_inactive(JPaginationObject $item)
	{
		$app = JFactory::getApplication();

                if ($app->isClient('administrator'))
		{
			return '<span>' . $item->text . '</span>';
		}
		else
		{
			return '<span class="pagenav">' . $item->text . '</span>';
		}
	}

	/**
	 * Create and return the pagination data object.
	 *
	 * @return  object  Pagination data object.
	 *
	 * @since   1.5
	 */
	protected function _buildDataObject()
	{
		$data = new stdClass;

		if (!$this->uri)
		{
			$this->uri = KunenaRoute::$current;
		}

		// Build the additional URL parameters string.
		foreach ($this->additionalUrlParams as $key => $value)
		{
			$this->uri->setVar($key, $value);
		}
		$limitstartKey = $this->prefix . 'limitstart';

		$data->all = new JPaginationObject(JText::_('JLIB_HTML_VIEW_ALL'), $this->prefix);
		if (!$this->viewall)
		{
			$this->uri->delVar($limitstartKey, '');
			$data->all->base = '0';
			$data->all->link = JRoute::_((string) $this->uri);
		}

		// Set the start and previous data objects.
		$data->start = new JPaginationObject(JText::_('JLIB_HTML_START'), $this->prefix);
		$data->previous = new JPaginationObject(JText::_('JPREV'), $this->prefix);

		if ($this->pagesCurrent > 1)
		{
			$page = ($this->pagesCurrent - 2) * $this->limit;

			$this->uri->setVar($limitstartKey, '0');
			$data->start->base = '0';
			$data->start->link = JRoute::_((string) $this->uri);

			$this->uri->setVar($limitstartKey, $page);
			$data->previous->base = $page;
			$data->previous->link = JRoute::_((string) $this->uri);
		}

		// Set the next and end data objects.
		$data->next = new JPaginationObject(JText::_('JNEXT'), $this->prefix);
		$data->end = new JPaginationObject(JText::_('JLIB_HTML_END'), $this->prefix);

		if ($this->pagesCurrent < $this->pagesTotal)
		{
			$next = $this->pagesCurrent * $this->limit;
			$end = ($this->pagesTotal - 1) * $this->limit;

			$this->uri->setVar($limitstartKey, $next);
			$data->next->base = $next;
			$data->next->link = JRoute::_((string) $this->uri);

			$this->uri->setVar($limitstartKey, $end);
			$data->end->base = $end;
			$data->end->link = JRoute::_((string) $this->uri);
		}

		$data->pages = array();
		$range = range($this->pagesStart, $this->pagesStop);

		$range[] = 1;
		$range[] = $this->pagesTotal;
		sort($range);

		foreach ($range as $i)
		{
			$offset = ($i - 1) * $this->limit;

			$data->pages[$i] = new JPaginationObject($i, $this->prefix);

			if ($i != $this->pagesCurrent || $this->viewall)
			{
				$this->uri->setVar($limitstartKey, $offset);
				$data->pages[$i]->base = $offset;
				$data->pages[$i]->link = JRoute::_((string) $this->uri);
			}
			elseif ($i == $this->pagesCurrent)
			{
				$data->pages[$i]->active = true;
			}
		}

		return $data;
	}

	/**
	 * Modifies a property of the object, creating it if it does not already exist.
	 *
	 * @param   string  $property  The name of the property.
	 * @param   mixed   $value     The value of the property to set.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 * @deprecated  4.0  Access the properties directly.
	 */
	public function set($property, $value = null)
	{
		JLog::add('JPagination::set() is deprecated. Access the properties directly.', JLog::WARNING, 'deprecated');

		if (strpos($property, '.'))
		{
			$prop = explode('.', $property);
			$prop[1] = ucfirst($prop[1]);
			$property = implode($prop);
		}

		$this->$property = $value;
	}

	/**
	 * Returns a property of the object or the default value if the property is not set.
	 *
	 * @param   string  $property  The name of the property.
	 * @param   mixed   $default   The default value.
	 *
	 * @return  mixed    The value of the property.
	 *
	 * @since   3.0
	 * @deprecated  4.0  Access the properties directly.
	 */
	public function get($property, $default = null)
	{
		JLog::add('JPagination::get() is deprecated. Access the properties directly.', JLog::WARNING, 'deprecated');

		if (strpos($property, '.'))
		{
			$prop = explode('.', $property);
			$prop[1] = ucfirst($prop[1]);
			$property = implode($prop);
		}

		if (isset($this->$property))
		{
			return $this->$property;
		}

		return $default;
	}
}
