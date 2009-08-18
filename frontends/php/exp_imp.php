<?php
/*
** ZABBIX
** Copyright (C) 2000-2009 SIA Zabbix
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
**/

	require_once('include/config.inc.php');
	require_once('include/forms.inc.php');
?>
<?php
	$_REQUEST['go'] = get_request('go','none');
	if(($_REQUEST['go'] == 'export') && isset($_REQUEST['hosts'])){
		$EXPORT_DATA = true;
		$page['type'] = PAGE_TYPE_XML;
		$page['file'] = 'zabbix_export.xml';
	}
	else{
		$page['title'] = "S_EXPORT_IMPORT";
		$page['file'] = 'exp_imp.php';
		$page['hist_arg'] = array('config','groupid');
	}

include_once('include/page_header.php');

	$_REQUEST['config'] = get_request('config',get_profile('web.exp_imp.config',0));

?>
<?php
	$fields=array(
//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
		'config'=>	array(T_ZBX_INT, O_OPT,	P_SYS,	IN("0,1"),	null), /* 0 - export, 1 - import */

		'groupid'=>	array(T_ZBX_INT, O_OPT,	null,	DB_ID,		null),
		'hosts'=>	array(T_ZBX_INT, O_OPT,	null,	DB_ID,		null),
		'templates'=>	array(T_ZBX_INT, O_OPT,	null,	DB_ID,		null),
		'items'=>	array(T_ZBX_INT, O_OPT,	null,	DB_ID,		null),
		'triggers'=>	array(T_ZBX_INT, O_OPT,	null,	DB_ID,		null),
		'graphs'=>	array(T_ZBX_INT, O_OPT,	null,	DB_ID,		null),

		'update'=>	array(T_ZBX_INT, O_OPT,	null,	DB_ID,		null),
		'rules'=>	array(T_ZBX_INT, O_OPT,	null,	DB_ID,		null),

//		'screens'=>	array(T_ZBX_INT, O_OPT,	null,	DB_ID,		null),

// Actions
		'go'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, 	NULL, NULL),

// form
		'preview'=>	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'export'=>	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'import'=>	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL)
	);

	check_fields($fields);
	validate_sort_and_sortorder('host',ZBX_SORT_UP);

	$_REQUEST['go'] = get_request('go','none');
	$preview = ($_REQUEST['go'] == 'preview')?true:false;
	$config = get_request('config', 0);
	$update = get_request('update', null);

	update_profile('web.exp_imp.config', $config, PROFILE_TYPE_INT);

?>
<?php
	if($config == 1){
		$rules = get_request('rules', array());
		foreach(array('host', 'template', 'item', 'trigger', 'graph') as $key){
			if(!isset($rules[$key]['exist']))	$rules[$key]['exist']	= 0;
			if(!isset($rules[$key]['missed']))	$rules[$key]['missed']	= 0;
		}

	}
	else{
		$params=array();
		$options = array('only_current_node');
		foreach($options as $option) $params[$option] = 1;

		$PAGE_GROUPS = get_viewed_groups(PERM_READ_ONLY, $params);
		$PAGE_HOSTS = get_viewed_hosts(PERM_READ_ONLY, $PAGE_GROUPS['selected'], $params);

		validate_group($PAGE_GROUPS,$PAGE_HOSTS, false);

		$available_hosts = $PAGE_HOSTS['hostids'];

		$hosts		= get_request('hosts', array());
		$templates	= get_request('templates', array());
		$items		= get_request('items', array());
		$graphs		= get_request('graphs', array());
		$triggers	= get_request('triggers', array());

		function zbx_array_val_inc($arr, $inc_size = 1){
			foreach($arr as $id => $val){
				$arr[$id] = $val + $inc_size;
			}
		return $arr;
		}

		$hosts		= zbx_array_val_inc(array_flip(array_intersect(array_keys($hosts),	$available_hosts)));
		$templates	= zbx_array_val_inc(array_flip(array_intersect(array_keys($templates),	array_keys($hosts))));
		$items		= zbx_array_val_inc(array_flip(array_intersect(array_keys($items),	array_keys($hosts))));
		$graphs		= zbx_array_val_inc(array_flip(array_intersect(array_keys($graphs),	array_keys($hosts))));
		$triggers	= zbx_array_val_inc(array_flip(array_intersect(array_keys($triggers),	array_keys($hosts))));

		if(count($hosts)==0) $hosts[-1] = 1;
	}

//die();
	if(isset($EXPORT_DATA)){
		include_once('include/export.inc.php');

		$exporter = new CZabbixXMLExport();
		$exporter->Export($hosts,$templates,$items,$triggers,$graphs);

		unset($exporter);
	}
?>
<?php
	switch($config){
		case 1:
			$title = S_IMPORT_BIG;
			$frm_title = S_IMPORT;
			break;
		case 0:
		default:
			$title = S_EXPORT_BIG;
			$frm_title = S_EXPORT;
	}

	$form = new CForm();
	$form->setMethod('get');

	$cmbConfig = new CComboBox('config', $config, 'submit()');
	$cmbConfig->addItem(0, S_EXPORT);
	$cmbConfig->addItem(1, S_IMPORT);
	$form->addItem($cmbConfig);

	show_table_header($title, $form);
	if($config == 1){
		if(isset($_FILES['import_file'])){
			include_once 'include/import.inc.php';

			DBstart();

			$importer = new CZabbixXMLImport();
			$importer->setRules($rules['host'],$rules['template'],$rules['item'],$rules['trigger'],$rules['graph']);
			$result = $importer->Parse($_FILES['import_file']['tmp_name']);
			unset($importer);
			$result = DBend($result);
			show_messages($result, S_IMPORTED.SPACE.S_SUCCESSEFULLY_SMALL, S_IMPORT.SPACE.S_FAILED_SMALL);
		}

		$form = new CFormTable($frm_title,null,'post','multipart/form-data');
		$form->addVar('config', $config);
		$form->addRow(S_IMPORT_FILE, new CFile('import_file'));

		$table = new CTable();
		$table->setHeader(array(S_ELEMENT, S_EXISTING, S_MISSING),'bold');

		foreach(array(
				'host'		=> S_HOST,
				'template'	=> S_TEMPLATE,
				'item'		=> S_ITEM,
				'trigger'	=> S_TRIGGER,
				'graph'		=> S_GRAPH)
			as $key => $title)
		{
			$cmbExist = new CComboBox('rules['.$key.'][exist]', $rules[$key]['exist']);
			$cmbExist->addItem(0, S_UPDATE);
			$cmbExist->addItem(1, S_SKIP);

			$cmbMissed = new CComboBox('rules['.$key.'][missed]', $rules[$key]['missed']);

			if($key != 'template')
				$cmbMissed->addItem(0, S_ADD);

			$cmbMissed->addItem(1, S_SKIP);

			$table->addRow(array($title, $cmbExist, $cmbMissed));
		}

		$form->addRow(S_RULES, $table);

		$form->addItemToBottomRow(new CButton('import', S_IMPORT));
		$form->Show();
	}
	else{
		echo SBR;
		if($preview){
			$table = new CTableInfo(S_NO_DATA_FOR_EXPORT);
			$table->setHeader(array(S_HOST, S_ELEMENTS));
			$table->showStart();

			$hostids = array_keys($hosts);
			$sql = 'SELECT * '.
					' FROM hosts '.
					' WHERE '.DBcondition('hostid',$hostids).
						' AND status IN ('.HOST_STATUS_MONITORED.','.HOST_STATUS_NOT_MONITORED.','.HOST_STATUS_TEMPLATE.')';
			$db_hosts = DBselect($sql);
			while($host = DBfetch($db_hosts)){
				$el_table = new CTableInfo(S_ONLY_HOST_INFO);
				$sqls = array(
					S_TEMPLATE	=> !isset($templates[$host['hostid']]) ? null :
								'SELECT MIN(ht.hostid) as hostid, h.host as info, count(distinct ht.hosttemplateid) as cnt '.
								' FROM hosts h, hosts_templates ht '.
								' WHERE ht.templateid = h.hostid '.
								' GROUP BY h.host',
					S_ITEM		=> !isset($items[$host['hostid']]) ? null :
								'SELECT hostid, description as info, 1 as cnt '.
								' FROM items'.
								' WHERE hostid='.$host['hostid'],
					S_TRIGGER	=> !isset($triggers[$host['hostid']]) ? null :
								'SELECT i.hostid, t.description as info, count(distinct i.hostid) as cnt, f.triggerid '.
								' FROM functions f, items i, triggers t'.
								' WHERE t.triggerid=f.triggerid'.
									' AND f.itemid=i.itemid'.
								' GROUP BY f.triggerid, i.hostid, t.description',
					S_GRAPH		=> !isset($graphs[$host['hostid']]) ? null :
								'SELECT MIN(g.name) as info, i.hostid, count(distinct i.hostid) as cnt, gi.graphid'.
								' FROM graphs_items gi, items i, graphs g '.
								' WHERE g.graphid=gi.graphid '.
									' AND gi.itemid=i.itemid'.
								' GROUP BY gi.graphid, i.hostid'

					);
				foreach($sqls as $el_type => $sql){
					if(!isset($sql)) continue;

					$db_els = DBselect($sql);
					while($el = DBfetch($db_els)){
						if($el['cnt'] != 1 || (bccomp($el['hostid'] , $host['hostid']) != 0)) continue;
						$el_table->addRow(array($el_type, $el['info']));
					}
				}

				$table->showRow(array(new CCol($host['host'], 'top'),$el_table));
				unset($el_table);
			}

			$form = new CForm(null,'post');
			$form->setName('hosts');
			$form->addVar('config',		$config);
			$form->addVar('update',		true);
			$form->addVar('groupid',	$PAGE_GROUPS['selected']);
			$form->addVar('hosts',		$hosts);
			$form->addVar('templates',	$templates);
			$form->addVar('items', 		$items);
			$form->addVar('graphs', 	$graphs);
			$form->addVar('triggers',	$triggers);

//----- GO ------
			$goBox = new CComboBox('go');
			$goBox->addItem('back',S_BACK);
			$goBox->addItem('preview',S_REFRESH);
			$goBox->addItem('export',S_EXPORT);

// goButton name is necessary!!!
			$goButton = new CButton('goButton',S_GO);
			$goButton->setAttribute('id','goButton');

			$form->addItem(array($goBox, $goButton));
//----
			$table->setFooter(new CCol($form));
			$table->showEnd();
		}
		else{
/* table HOSTS */
			$export_wdgt = new CWidget();

			$form = new CForm(null,'post');
			$form->setName('hosts');
			$form->addVar('config',$config);

			$cmbGroups = new CComboBox('groupid',$PAGE_GROUPS['selected'],'javascript: submit();');
			foreach($PAGE_GROUPS['groups'] as $groupid => $name){
				$cmbGroups->addItem($groupid, get_node_name_by_elid($groupid).$name);
			}

			$form->addItem(array(S_GROUP.SPACE, $cmbGroups));

			$numrows = new CDiv();
			$numrows->setAttribute('name','numrows');

			$export_wdgt->addHeader(S_HOSTS_BIG, $form);
			$export_wdgt->addHeader($numrows);

// export table
			$form = new CForm(null,'post');
			$form->setName('hosts_export');
			$form->addVar('config',$config);
			$form->addVar('update', true);

			$table = new CTableInfo(S_NO_HOSTS_DEFINED);
			$table->setHeader(array(
				new CCheckBox('all_hosts',true, "checkAll('".$form->getName()."','all_hosts','hosts');"),
				make_sorting_header(S_NAME,'host'),
				make_sorting_header(S_DNS,'dns'),
				make_sorting_header(S_IP,'ip'),
				make_sorting_header(S_PORT,'port'),
				make_sorting_header(S_STATUS,'status'),
				array(	new CCheckBox("all_templates",true, "checkAll('".$form->getName()."','all_templates','templates');"),
					S_TEMPLATES),
				array(	new CCheckBox("all_items",true, "checkAll('".$form->getName()."','all_items','items');"),
					S_ITEMS),
				array(	new CCheckBox("all_triggers",true, "checkAll('".$form->getName()."','all_triggers','triggers');"),
					S_TRIGGERS),
				array(	new CCheckBox("all_graphs",true, "checkAll('".$form->getName()."','all_graphs','graphs');"),
					S_GRAPHS)
				/*
				array(	new CCheckBox("all_screens",true, "checkAll('".$form->getName()."','all_screens','screens');")
					S_GRAPHS)
				*/
				));


			$sql_from = '';
			$sql_where = '';
			if($_REQUEST['groupid']>0){
				$sql_from.= ' ,hosts_groups hg ';
				$sql_where.= ' AND hg.groupid='.$_REQUEST['groupid'].
							' AND hg.hostid=h.hostid ';
			}

			$hosts = array();
			$hostids = array();
			$sql = 'SELECT DISTINCT h.* '.
					' FROM hosts h '.$sql_from.
					' WHERE '.DBcondition('h.hostid',$available_hosts).
						$sql_where.
						' AND h.status IN ('.HOST_STATUS_MONITORED.','.HOST_STATUS_NOT_MONITORED.','.HOST_STATUS_TEMPLATE.')'.
					order_by('h.host,h.dns,h.ip,h.port,h.status');

			$result=DBselect($sql);
			while($host=DBfetch($result)){
				$hosts[$host['hostid']] = $host;
				$hostids[$host['hostid']] = $host['hostid'];
			}
// templates
			$sql = 'SELECT hostid,count(hosttemplateid) as cnt '.
					' FROM hosts_templates '.
					' WHERE '.DBcondition('hostid',$hostids).
					' GROUP BY hostid';
			$result = DBselect($sql);
			while($templates=DBfetch($result)){
				$hosts[$templates['hostid']]['templates_cnt'] = $templates['cnt'];
			}
// items
			$sql = 'SELECT hostid,count(itemid) as cnt '.
					' FROM items '.
					' WHERE '.DBcondition('hostid',$hostids).
					' GROUP BY hostid';
			$result = DBselect($sql);
			while($items=DBfetch($result)){
				$hosts[$items['hostid']]['items_cnt'] = $items['cnt'];
			}
// triggers
			$sql = 'SELECT count(DISTINCT f.triggerid) as cnt, i.hostid '.
					' FROM functions f, items i '.
					' WHERE f.itemid=i.itemid '.
						' AND '.DBcondition('i.hostid',$hostids).
					' GROUP BY i.hostid';
			$result = DBselect($sql);
			while($triggers=DBfetch($result)){
				$hosts[$triggers['hostid']]['triggers_cnt'] = $triggers['cnt'];
			}
// graphs
			$sql = 'SELECT count(DISTINCT gi.graphid) as cnt, i.hostid '.
					' FROM graphs_items gi, items i '.
					' WHERE gi.itemid=i.itemid '.
						' AND '.DBcondition('i.hostid',$hostids).
					' GROUP BY i.hostid';
			$result = DBselect($sql);
			while($graphs=DBfetch($result)){
				$hosts[$graphs['hostid']]['graphs_cnt'] = $graphs['cnt'];
			}

// sorting
			order_page_result($hosts, getPageSortField('host'), getPageSortOrder());
			$paging = getPagingLine($hosts);
//-------

			$count_chkbx = 0;
			foreach($hosts as $hostid => $host){
				$status = new CCol(host_status2str($host['status']),host_status2style($host['status']));

				/* calculate template */
				if(isset($host['templates_cnt']) && ($host['templates_cnt'] > 0)){
					$template_cnt = array(new CCheckBox('templates['.$host['hostid'].']',
							isset($templates[$host['hostid']]) || !isset($update),
							NULL,true),
						$host['templates_cnt']);
				}
				else{
					$template_cnt = '-';
				}

				/* calculate items */

				if(isset($host['items_cnt']) && ($host['items_cnt'] > 0)){
					$item_cnt = array(new CCheckBox('items['.$host['hostid'].']',
							isset($items[$host['hostid']]) || !isset($update),
							NULL,true),
						$host['items_cnt']);
				}
				else{
					$item_cnt = '-';
				}

				/* calculate triggers */
				if(isset($host['triggers_cnt']) && ($host['triggers_cnt'] > 0)){
					$trigger_cnt = array(new CCheckBox('triggers['.$host['hostid'].']',
							isset($triggers[$host['hostid']]) || !isset($update),
							NULL,true),
						$host['triggers_cnt']);
				}
				else{
					$trigger_cnt = '-';
				}

				/* calculate graphs */
				if(isset($host['graphs_cnt']) && ($host['graphs_cnt'] > 0)){
					$graph_cnt = array(new CCheckBox('graphs['.$host['hostid'].']',
							isset($graphs[$host['hostid']]) || !isset($update),
							NULL,true),
						$host['graphs_cnt']);
				}
				else{
					$graph_cnt = '-';
				}

				/* $screens = 0; */
				if($host['status'] == HOST_STATUS_TEMPLATE){
					$ip = $dns = $port = '-';
				}
				else{
					$ip = (empty($host['ip']))?'-':$host['ip'];
					$dns = (empty($host['dns']))?'-':$host['dns'];

					if($host['useip']==1)
						$ip = bold($ip);
					else
						$dns = bold($dns);

					$port = (empty($host['port']))?'-':$host['port'];
				}

				$checked = (isset($hosts[$host['hostid']]) || !isset($update));
				if($checked) $count_chkbx++;

				$table->addRow(array(
					new CCheckBox('hosts['.$host['hostid'].']',$checked,NULL,true),
					$host['host'],
					$dns,
					$ip,
					$port,
					$status,
					$template_cnt,
					$item_cnt,
					$trigger_cnt,
					$graph_cnt
					/*,
					array(new CCheckBox('screens['.$row['hostid'].']',
							isset($screens[$row['hostid']]) || !isset($update),
							NULL,true),
						$screens)*/
					));
			}
// goBox
			$goBox = new CComboBox('go');
			$goBox->addItem('preview',S_PREVIEW);
			$goBox->addItem('export',S_EXPORT);

			// goButton name is necessary!!!
			$goButton = new CButton('goButton',S_GO.' ('.$count_chkbx.')');
			$goButton->setAttribute('id','goButton');
			zbx_add_post_js('chkbxRange.pageGoName = "hosts";');

			$footer = get_table_header(new CCol(array($goBox, $goButton)));
//----

// PAGING FOOTER
			$table = array($paging,$table,$paging,$footer);
//---------

			$form->addItem($table);

			$export_wdgt->addItem($form);
			$export_wdgt->show();
		}
	}

?>
<?php

include_once('include/page_footer.php');

?>
