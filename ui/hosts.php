<?php
/*
** Zabbix
** Copyright (C) 2001-2021 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


require_once dirname(__FILE__).'/include/config.inc.php';
require_once dirname(__FILE__).'/include/forms.inc.php';

$page['title'] = _('Configuration of hosts');
$page['file'] = 'hosts.php';
$page['type'] = detect_page_type(PAGE_TYPE_HTML);
$page['scripts'] = ['multiselect.js', 'textareaflexible.js', 'class.cviewswitcher.js', 'class.cverticalaccordion.js',
	'inputsecret.js', 'macrovalue.js', 'class.tab-indicators.js'
];

require_once dirname(__FILE__).'/include/page_header.php';

// VAR	TYPE	OPTIONAL	FLAGS	VALIDATION	EXCEPTION
$fields = [
	'hosts' =>					[T_ZBX_INT, O_OPT, P_SYS,			DB_ID,		null],
	'groups' =>					[T_ZBX_STR, O_OPT, null,			NOT_EMPTY,	'isset({add}) || isset({update})'],
	'hostids' =>				[T_ZBX_INT, O_OPT, P_SYS,			DB_ID,		null],
	'groupids' =>				[T_ZBX_INT, O_OPT, P_SYS,			DB_ID,		null],
	'applications' =>			[T_ZBX_INT, O_OPT, P_SYS,			DB_ID,		null],
	'hostid' =>					[T_ZBX_INT, O_OPT, P_SYS,			DB_ID,		'isset({form}) && {form} == "update"'],
	'clone_hostid' =>			[T_ZBX_INT, O_OPT, P_SYS,			DB_ID,
									'isset({form}) && ({form} == "clone" || {form} == "full_clone")'
								],
	'host' =>					[T_ZBX_STR, O_OPT, null,			NOT_EMPTY,	'isset({add}) || isset({update})',
									_('Host name')
								],
	'visiblename' =>			[T_ZBX_STR, O_OPT, null,			null,		'isset({add}) || isset({update})'],
	'description' =>			[T_ZBX_STR, O_OPT, null,			null,		null],
	'proxy_hostid' =>			[T_ZBX_INT, O_OPT, P_SYS,		    DB_ID,		null],
	'status' =>					[T_ZBX_INT, O_OPT, null,
									IN([HOST_STATUS_MONITORED, HOST_STATUS_NOT_MONITORED]), null
								],
	'interfaces' =>				[T_ZBX_STR, O_OPT, null,			null,		null],
	'mainInterfaces' =>			[T_ZBX_INT, O_OPT, null,			DB_ID,		null],
	'tags' =>					[T_ZBX_STR, O_OPT, null,			null,		null],
	'templates' =>				[T_ZBX_INT, O_OPT, null,			DB_ID,		null],
	'add_templates' =>			[T_ZBX_INT, O_OPT, null,			DB_ID,		null],
	'templates_rem' =>			[T_ZBX_STR, O_OPT, P_SYS|P_ACT,		null,		null],
	'clear_templates' =>		[T_ZBX_INT, O_OPT, null,			DB_ID,		null],
	'ipmi_authtype' =>			[T_ZBX_INT, O_OPT, null,			BETWEEN(-1, 6), null],
	'ipmi_privilege' =>			[T_ZBX_INT, O_OPT, null,			BETWEEN(0, 5), null],
	'ipmi_username' =>			[T_ZBX_STR, O_OPT, null,			null,		null],
	'ipmi_password' =>			[T_ZBX_STR, O_OPT, null,			null,		null],
	'tls_connect' =>			[T_ZBX_INT, O_OPT, null,
									IN([HOST_ENCRYPTION_NONE, HOST_ENCRYPTION_PSK, HOST_ENCRYPTION_CERTIFICATE]),
									null
								],
	'tls_accept' =>				[T_ZBX_INT, O_OPT, null,
									BETWEEN(0,
										(HOST_ENCRYPTION_NONE | HOST_ENCRYPTION_PSK | HOST_ENCRYPTION_CERTIFICATE)
									),
									null
								],
	'tls_subject' =>			[T_ZBX_STR, O_OPT, null,			null,		null],
	'tls_issuer' =>				[T_ZBX_STR, O_OPT, null,			null,		null],
	'tls_psk_identity' =>		[T_ZBX_STR, O_OPT, null,			null,		null],
	'tls_psk' =>				[T_ZBX_STR, O_OPT, null,			null,		null],
	'psk_edit_mode' =>			[T_ZBX_INT, O_OPT, null,			IN([0,1]),	null],
	'flags' =>					[T_ZBX_INT, O_OPT, null,
									IN([ZBX_FLAG_DISCOVERY_NORMAL, ZBX_FLAG_DISCOVERY_CREATED]), null
								],
	'inventory_mode' =>			[T_ZBX_INT, O_OPT, null,
									IN(HOST_INVENTORY_DISABLED.','.HOST_INVENTORY_MANUAL.','.HOST_INVENTORY_AUTOMATIC),
									null
								],
	'host_inventory' =>			[T_ZBX_STR, O_OPT, P_UNSET_EMPTY,	null,		null],
	'macros' =>					[T_ZBX_STR, O_OPT, P_SYS,			null,		null],
	'visible' =>				[T_ZBX_STR, O_OPT, null,			null,		null],
	'show_inherited_macros' =>	[T_ZBX_INT, O_OPT, null, IN([0,1]), null],
	'valuemaps' => 				[T_ZBX_STR, O_OPT, null,		null,	null],
	// actions
	'action' =>					[T_ZBX_STR, O_OPT, P_SYS|P_ACT,
									IN('"host.export","host.massdelete","host.massdisable", "host.massenable"'),
									null
								],
	'unlink' =>					[T_ZBX_STR, O_OPT, P_SYS|P_ACT,		null,		null],
	'unlink_and_clear' =>		[T_ZBX_STR, O_OPT, P_SYS|P_ACT,		null,		null],
	'add' =>					[T_ZBX_STR, O_OPT, P_SYS|P_ACT,		null,		null],
	'update' =>					[T_ZBX_STR, O_OPT, P_SYS|P_ACT,		null,		null],
	'clone' =>					[T_ZBX_STR, O_OPT, P_SYS|P_ACT,		null,		null],
	'full_clone' =>				[T_ZBX_STR, O_OPT, P_SYS|P_ACT,		null,		null],
	'delete' =>					[T_ZBX_STR, O_OPT, P_SYS|P_ACT,		null,		null],
	'cancel' =>					[T_ZBX_STR, O_OPT, P_SYS,			null,		null],
	'form' =>					[T_ZBX_STR, O_OPT, P_SYS,			null,		null],
	'form_refresh' =>			[T_ZBX_INT, O_OPT, null,			null,		null],
	// filter
	'filter_set' =>				[T_ZBX_STR, O_OPT, P_SYS,			null,		null],
	'filter_rst' =>				[T_ZBX_STR, O_OPT, P_SYS,			null,		null],
	'filter_host' =>			[T_ZBX_STR, O_OPT, null,			null,		null],
	'filter_templates' =>		[T_ZBX_INT, O_OPT, null,			DB_ID,		null],
	'filter_groups' =>			[T_ZBX_INT, O_OPT, null,			DB_ID,		null],
	'filter_ip' =>				[T_ZBX_STR, O_OPT, null,			null,		null],
	'filter_dns' =>				[T_ZBX_STR, O_OPT, null,			null,		null],
	'filter_port' =>			[T_ZBX_STR, O_OPT, null,			null,		null],
	'filter_monitored_by' =>	[T_ZBX_INT, O_OPT, null,
									IN([ZBX_MONITORED_BY_ANY, ZBX_MONITORED_BY_SERVER, ZBX_MONITORED_BY_PROXY]),
									null
								],
	'filter_proxyids' =>		[T_ZBX_INT, O_OPT, null,			DB_ID,		null],
	'filter_evaltype' =>		[T_ZBX_INT, O_OPT, null,
									IN([TAG_EVAL_TYPE_AND_OR, TAG_EVAL_TYPE_OR]),
									null
								],
	'filter_tags' =>			[T_ZBX_STR, O_OPT, null,			null,		null],
	// sort and sortorder
	'sort' =>					[T_ZBX_STR, O_OPT, P_SYS, IN('"name","status"'),						null],
	'sortorder' =>				[T_ZBX_STR, O_OPT, P_SYS, IN('"'.ZBX_SORT_DOWN.'","'.ZBX_SORT_UP.'"'),	null]
];
check_fields($fields);

/*
 * Permissions
 */
if (getRequest('hostid')) {
	$hosts = API::Host()->get([
		'output' => [],
		'hostids' => getRequest('hostid'),
		'editable' => true
	]);

	if (!$hosts) {
		access_deny();
	}
}

/*
 * Filter
 */
if (hasRequest('filter_set')) {
	CProfile::update('web.hosts.filter_ip', getRequest('filter_ip', ''), PROFILE_TYPE_STR);
	CProfile::update('web.hosts.filter_dns', getRequest('filter_dns', ''), PROFILE_TYPE_STR);
	CProfile::update('web.hosts.filter_host', getRequest('filter_host', ''), PROFILE_TYPE_STR);
	CProfile::update('web.hosts.filter_port', getRequest('filter_port', ''), PROFILE_TYPE_STR);
	CProfile::update('web.hosts.filter_monitored_by', getRequest('filter_monitored_by', ZBX_MONITORED_BY_ANY),
		PROFILE_TYPE_INT
	);
	CProfile::updateArray('web.hosts.filter_templates', getRequest('filter_templates', []), PROFILE_TYPE_ID);
	CProfile::updateArray('web.hosts.filter_groups', getRequest('filter_groups', []), PROFILE_TYPE_ID);
	CProfile::updateArray('web.hosts.filter_proxyids', getRequest('filter_proxyids', []), PROFILE_TYPE_ID);
	CProfile::update('web.hosts.filter.evaltype', getRequest('filter_evaltype', TAG_EVAL_TYPE_AND_OR),
		PROFILE_TYPE_INT
	);

	$filter_tags = ['tags' => [], 'values' => [], 'operators' => []];
	foreach (getRequest('filter_tags', []) as $filter_tag) {
		if ($filter_tag['tag'] === '' && $filter_tag['value'] === '') {
			continue;
		}

		$filter_tags['tags'][] = $filter_tag['tag'];
		$filter_tags['values'][] = $filter_tag['value'];
		$filter_tags['operators'][] = $filter_tag['operator'];
	}
	CProfile::updateArray('web.hosts.filter.tags.tag', $filter_tags['tags'], PROFILE_TYPE_STR);
	CProfile::updateArray('web.hosts.filter.tags.value', $filter_tags['values'], PROFILE_TYPE_STR);
	CProfile::updateArray('web.hosts.filter.tags.operator', $filter_tags['operators'], PROFILE_TYPE_INT);
}
elseif (hasRequest('filter_rst')) {
	DBstart();
	CProfile::delete('web.hosts.filter_ip');
	CProfile::delete('web.hosts.filter_dns');
	CProfile::delete('web.hosts.filter_host');
	CProfile::delete('web.hosts.filter_port');
	CProfile::delete('web.hosts.filter_monitored_by');
	CProfile::deleteIdx('web.hosts.filter_templates');
	CProfile::deleteIdx('web.hosts.filter_groups');
	CProfile::deleteIdx('web.hosts.filter_proxyids');
	CProfile::delete('web.hosts.filter.evaltype');
	CProfile::deleteIdx('web.hosts.filter.tags.tag');
	CProfile::deleteIdx('web.hosts.filter.tags.value');
	CProfile::deleteIdx('web.hosts.filter.tags.operator');
	DBend();
}

$filter = [
	'ip' => CProfile::get('web.hosts.filter_ip', ''),
	'dns' => CProfile::get('web.hosts.filter_dns', ''),
	'host' => CProfile::get('web.hosts.filter_host', ''),
	'templates' => CProfile::getArray('web.hosts.filter_templates', []),
	'groups' => CProfile::getArray('web.hosts.filter_groups', []),
	'port' => CProfile::get('web.hosts.filter_port', ''),
	'monitored_by' => CProfile::get('web.hosts.filter_monitored_by', ZBX_MONITORED_BY_ANY),
	'proxyids' => CProfile::getArray('web.hosts.filter_proxyids', []),
	'evaltype' => CProfile::get('web.hosts.filter.evaltype', TAG_EVAL_TYPE_AND_OR),
	'tags' => []
];

foreach (CProfile::getArray('web.hosts.filter.tags.tag', []) as $i => $tag) {
	$filter['tags'][] = [
		'tag' => $tag,
		'value' => CProfile::get('web.hosts.filter.tags.value', null, $i),
		'operator' => CProfile::get('web.hosts.filter.tags.operator', null, $i)
	];
}
CArrayHelper::sort($filter['tags'], ['tag', 'value', 'operator']);

$tags = getRequest('tags', []);
foreach ($tags as $key => $tag) {
	// remove empty new tag lines
	if ($tag['tag'] === '' && $tag['value'] === '') {
		unset($tags[$key]);
		continue;
	}

	// remove inherited tags
	if (array_key_exists('type', $tag) && !($tag['type'] & ZBX_PROPERTY_OWN)) {
		unset($tags[$key]);
	}
	else {
		unset($tags[$key]['type']);
	}
}

// Remove inherited macros data (actions: 'add', 'update' and 'form').
$macros = cleanInheritedMacros(getRequest('macros', []));

// Remove empty new macro lines.
$macros = array_filter($macros, function($macro) {
	$keys = array_flip(['hostmacroid', 'macro', 'value', 'description']);

	return (bool) array_filter(array_intersect_key($macro, $keys));
});

/*
 * Actions
 */
if (hasRequest('unlink') || hasRequest('unlink_and_clear')) {
	$_REQUEST['clear_templates'] = getRequest('clear_templates', []);

	$unlinkTemplates = [];

	if (isset($_REQUEST['unlink'])) {
		// templates_rem for old style removal in massupdate form
		if (isset($_REQUEST['templates_rem'])) {
			$unlinkTemplates = array_keys($_REQUEST['templates_rem']);
		}
		elseif (is_array($_REQUEST['unlink'])) {
			$unlinkTemplates = array_keys($_REQUEST['unlink']);
		}
	}
	else {
		$unlinkTemplates = array_keys($_REQUEST['unlink_and_clear']);

		$_REQUEST['clear_templates'] = array_merge($_REQUEST['clear_templates'], $unlinkTemplates);
	}

	foreach ($unlinkTemplates as $templateId) {
		unset($_REQUEST['templates'][array_search($templateId, $_REQUEST['templates'])]);
	}
}
elseif (hasRequest('hostid') && (hasRequest('clone') || hasRequest('full_clone'))) {
	$_REQUEST['form'] = hasRequest('clone') ? 'clone' : 'full_clone';

	$groups = getRequest('groups', []);
	$groupids = [];

	// Remove inaccessible groups from request, but leave "new".
	foreach ($groups as $group) {
		if (!is_array($group)) {
			$groupids[] = $group;
		}
	}

	if ($groupids) {
		$groups_allowed = API::HostGroup()->get([
			'output' => [],
			'groupids' => $groupids,
			'editable' => true,
			'preservekeys' => true
		]);

		foreach ($groups as $idx => $group) {
			if (!is_array($group) && !array_key_exists($group, $groups_allowed)) {
				unset($groups[$idx]);
			}
		}

		$_REQUEST['groups'] = $groups;
	}

	if (hasRequest('interfaces')) {
		$interfaceid = 1;
		foreach ($_REQUEST['interfaces'] as &$interface) {
			$interface['interfaceid'] = (string) $interfaceid++;
			unset($interface['items']);
		}
		unset($interface);
	}

	if (hasRequest('clone') || hasRequest('full_clone')) {
		$_REQUEST['clone_hostid'] = $_REQUEST['hostid'];
	}

	if ($macros && in_array(ZBX_MACRO_TYPE_SECRET, array_column($macros, 'type'))) {
		// Reset macro type and value.
		$macros = array_map(function($value) {
			return ($value['type'] == ZBX_MACRO_TYPE_SECRET)
				? ['value' => '', 'type' => ZBX_MACRO_TYPE_TEXT] + $value
				: $value;
		}, $macros);

		$msg = [
			'type' => 'error',
			'message' => _('The cloned host contains user defined macros with type "Secret text". The value and type of these macros were reset.'),
			'source' => ''
		];

		echo makeMessageBox(false, [$msg], null, true, false)->addClass(ZBX_STYLE_MSG_WARNING);
	}

	unset($_REQUEST['hostid'], $_REQUEST['flags']);
}
elseif (hasRequest('add') || hasRequest('update')) {
	try {
		DBstart();

		$hostId = getRequest('hostid', 0);

		if ($hostId != 0) {
			$create = false;

			$msgOk = _('Host updated');
			$msgFail = _('Cannot update host');

			$dbHost = API::Host()->get([
				'output' => ['hostid', 'host', 'name', 'status', 'description', 'proxy_hostid', 'ipmi_authtype',
					'ipmi_privilege', 'ipmi_username', 'ipmi_password', 'tls_connect', 'tls_accept', 'tls_issuer',
					'tls_subject', 'flags'
				],
				'hostids' => $hostId,
				'editable' => true
			]);
			$dbHost = reset($dbHost);
		}
		else {
			$create = true;

			$msgOk = _('Host added');
			$msgFail = _('Cannot add host');
		}

		// host data
		if (!$create && $dbHost['flags'] == ZBX_FLAG_DISCOVERY_CREATED) {
			$host = [
				'hostid' => $hostId,
				'status' => getRequest('status', HOST_STATUS_NOT_MONITORED),
				'description' => getRequest('description', ''),
				'inventory' => (getRequest('inventory_mode') == HOST_INVENTORY_DISABLED)
					? []
					: getRequest('host_inventory', [])
			];
		}
		else {
			// Linked templates.
			$templates = [];

			foreach (array_merge(getRequest('templates', []), getRequest('add_templates', [])) as $templateid) {
				$templates[] = ['templateid' => $templateid];
			}

			// interfaces
			$interfaces = getRequest('interfaces', []);

			foreach ($interfaces as $key => $interface) {
				// Process SNMP interface fields.
				if ($interface['type'] == INTERFACE_TYPE_SNMP) {
					if (!array_key_exists('details', $interface)) {
						$interface['details'] = [];
					}

					$interfaces[$key]['details']['bulk'] = array_key_exists('bulk', $interface['details'])
						? SNMP_BULK_ENABLED
						: SNMP_BULK_DISABLED;
				}

				if ($interface['isNew']) {
					unset($interfaces[$key]['interfaceid']);
				}

				unset($interfaces[$key]['isNew']);
				$interfaces[$key]['main'] = 0;
			}

			$mainInterfaces = getRequest('mainInterfaces', []);
			foreach ([INTERFACE_TYPE_AGENT, INTERFACE_TYPE_SNMP, INTERFACE_TYPE_JMX, INTERFACE_TYPE_IPMI] as $type) {
				if (array_key_exists($type, $mainInterfaces) && array_key_exists($mainInterfaces[$type], $interfaces)) {
					$interfaces[$mainInterfaces[$type]]['main'] = INTERFACE_PRIMARY;
				}
			}

			// Add new group.
			$groups = getRequest('groups', []);
			$new_groups = [];

			foreach ($groups as $idx => $group) {
				if (is_array($group) && array_key_exists('new', $group)) {
					$new_groups[] = ['name' => $group['new']];
					unset($groups[$idx]);
				}
			}

			if ($new_groups) {
				$new_groupid = API::HostGroup()->create($new_groups);

				if (!$new_groupid) {
					throw new Exception();
				}

				$groups = array_merge($groups, $new_groupid['groupids']);
			}

			// Host data.
			$host = [
				'host' => getRequest('host'),
				'name' => getRequest('visiblename'),
				'status' => getRequest('status', HOST_STATUS_NOT_MONITORED),
				'description' => getRequest('description'),
				'proxy_hostid' => getRequest('proxy_hostid', 0),
				'ipmi_authtype' => getRequest('ipmi_authtype'),
				'ipmi_privilege' => getRequest('ipmi_privilege'),
				'ipmi_username' => getRequest('ipmi_username'),
				'ipmi_password' => getRequest('ipmi_password'),
				'tls_connect' => getRequest('tls_connect', HOST_ENCRYPTION_NONE),
				'tls_accept' => getRequest('tls_accept', HOST_ENCRYPTION_NONE),
				'groups' => zbx_toObject($groups, 'groupid'),
				'templates' => $templates,
				'interfaces' => $interfaces,
				'tags' => $tags,
				'macros' => $macros,
				'inventory_mode' => getRequest('inventory_mode'),
				'inventory' => (getRequest('inventory_mode') == HOST_INVENTORY_DISABLED)
					? []
					: getRequest('host_inventory', [])
			];

			if ($host['tls_connect'] == HOST_ENCRYPTION_PSK || ($host['tls_accept'] & HOST_ENCRYPTION_PSK)) {
				// Add values to PSK fields from cloned host.
				if ($create && (getRequest('form', '') === 'clone' || getRequest('form', '') === 'full_clone')
						&& getRequest('clone_hostid', 0) != 0) {
					$clone_hosts = API::Host()->get([
						'output' => ['tls_psk_identity', 'tls_psk'],
						'hostids' => getRequest('clone_hostid'),
						'editable' => true
					]);
					$clone_host = reset($clone_hosts);

					$host['tls_psk_identity'] = $clone_host['tls_psk_identity'];
					$host['tls_psk'] = $clone_host['tls_psk'];
				}

				if (hasRequest('tls_psk_identity')) {
					$host['tls_psk_identity'] = getRequest('tls_psk_identity');
				}

				if (hasRequest('tls_psk')) {
					$host['tls_psk'] = getRequest('tls_psk');
				}
			}

			if ($host['tls_connect'] == HOST_ENCRYPTION_CERTIFICATE
					|| ($host['tls_accept'] & HOST_ENCRYPTION_CERTIFICATE)) {
				$host['tls_issuer'] = getRequest('tls_issuer', '');
				$host['tls_subject'] = getRequest('tls_subject', '');
			}

			if (!$create) {
				$host['templates_clear'] = zbx_toObject(getRequest('clear_templates', []), 'templateid');
			}
		}

		if ($create) {
			$hostIds = API::Host()->create($host);

			if ($hostIds) {
				$hostId = reset($hostIds['hostids']);
			}
			else {
				throw new Exception();
			}
		}
		else {
			$host['hostid'] = $hostId;

			if (!API::Host()->update($host)) {
				throw new Exception();
			}
		}

		$valuemaps = array_values(getRequest('valuemaps', []));
		$ins_valuemaps = [];
		$upd_valuemaps = [];
		$del_valuemapids = [];

		if ((getRequest('form', '') === 'full_clone' || getRequest('form', '') === 'clone')
				&& getRequest('clone_hostid', 0)) {
			foreach ($valuemaps as &$valuemap) {
				unset($valuemap['valuemapid']);
			}
			unset($valuemap);
		}
		else if ($hostId) {
			$del_valuemapids = API::ValueMap()->get([
				'output' => [],
				'hostids' => $hostId,
				'preservekeys' => true
			]);
		}

		foreach ($valuemaps as $valuemap) {
			if (array_key_exists('valuemapid', $valuemap)) {
				$upd_valuemaps[] = $valuemap;
				unset($del_valuemapids[$valuemap['valuemapid']]);
			}
			else {
				$ins_valuemaps[] = $valuemap + ['hostid' => $hostId];
			}
		}

		if ($upd_valuemaps && !API::ValueMap()->update($upd_valuemaps)) {
			throw new Exception();
		}

		if ($ins_valuemaps && !API::ValueMap()->create($ins_valuemaps)) {
			throw new Exception();
		}

		if ($del_valuemapids && !API::ValueMap()->delete(array_keys($del_valuemapids))) {
			throw new Exception();
		}

		// full clone
		if (getRequest('form', '') === 'full_clone' && getRequest('clone_hostid', 0) != 0) {
			$srcHostId = getRequest('clone_hostid');

			// copy applications
			if (!copyApplications($srcHostId, $hostId)) {
				throw new Exception();
			}

			/*
			 * First copy web scenarios with web items, so that later regular items can use web item as their master
			 * item.
			 */
			if (!copyHttpTests($srcHostId, $hostId)) {
				throw new Exception();
			}

			if (!copyItems($srcHostId, $hostId)) {
				throw new Exception();
			}

			// copy triggers
			$dbTriggers = API::Trigger()->get([
				'output' => ['triggerid'],
				'hostids' => $srcHostId,
				'inherited' => false,
				'filter' => ['flags' => ZBX_FLAG_DISCOVERY_NORMAL]
			]);

			if ($dbTriggers && !copyTriggersToHosts(zbx_objectValues($dbTriggers, 'triggerid'), $hostId, $srcHostId)) {
				throw new Exception();
			}

			// copy discovery rules
			$dbDiscoveryRules = API::DiscoveryRule()->get([
				'output' => ['itemid'],
				'hostids' => $srcHostId,
				'inherited' => false
			]);

			if ($dbDiscoveryRules) {
				$copyDiscoveryRules = API::DiscoveryRule()->copy([
					'discoveryids' => zbx_objectValues($dbDiscoveryRules, 'itemid'),
					'hostids' => [$hostId]
				]);

				if (!$copyDiscoveryRules) {
					throw new Exception();
				}
			}

			// copy graphs
			$dbGraphs = API::Graph()->get([
				'output' => API_OUTPUT_EXTEND,
				'selectHosts' => ['hostid'],
				'selectItems' => ['type'],
				'hostids' => $srcHostId,
				'filter' => ['flags' => ZBX_FLAG_DISCOVERY_NORMAL],
				'inherited' => false
			]);

			foreach ($dbGraphs as $dbGraph) {
				if (count($dbGraph['hosts']) > 1) {
					continue;
				}

				if (httpItemExists($dbGraph['items'])) {
					continue;
				}

				if (!copyGraphToHost($dbGraph['graphid'], $hostId)) {
					throw new Exception();
				}
			}
		}

		$result = DBend(true);

		if ($result) {
			uncheckTableRows();
		}
		show_messages($result, $msgOk, $msgFail);

		unset($_REQUEST['form'], $_REQUEST['hostid']);
	}
	catch (Exception $e) {
		DBend(false);
		show_messages(false, $msgOk, $msgFail);
	}
}
elseif (hasRequest('delete') && hasRequest('hostid')) {
	DBstart();

	$result = API::Host()->delete([getRequest('hostid')]);
	$result = DBend($result);

	if ($result) {
		unset($_REQUEST['form'], $_REQUEST['hostid']);
		uncheckTableRows();
	}
	show_messages($result, _('Host deleted'), _('Cannot delete host'));

	unset($_REQUEST['delete']);
}
elseif (hasRequest('hosts') && hasRequest('action') && getRequest('action') === 'host.massdelete') {
	DBstart();

	$result = API::Host()->delete(getRequest('hosts'));
	$result = DBend($result);

	if ($result) {
		uncheckTableRows();
	}
	else {
		$hostids = API::Host()->get([
			'output' => [],
			'hostids' => getRequest('hosts'),
			'editable' => true
		]);
		uncheckTableRows(getRequest('hostid'), zbx_objectValues($hostids, 'hostid'));
	}
	show_messages($result, _('Host deleted'), _('Cannot delete host'));
}
elseif (hasRequest('hosts') && hasRequest('action') && str_in_array(getRequest('action'), ['host.massenable', 'host.massdisable'])) {
	$enable = (getRequest('action') === 'host.massenable');
	$status = $enable ? TRIGGER_STATUS_ENABLED : TRIGGER_STATUS_DISABLED;

	$actHosts = API::Host()->get([
		'hostids' => getRequest('hosts'),
		'editable' => true,
		'templated_hosts' => true,
		'output' => ['hostid']
	]);

	if ($actHosts) {
		foreach ($actHosts as &$host) {
			$host['status'] = $status;
		}
		unset($host);

		$result = (bool) API::Host()->update($actHosts);

		if ($result) {
			uncheckTableRows();
		}

		$updated = count($actHosts);

		$messageSuccess = $enable
			? _n('Host enabled', 'Hosts enabled', $updated)
			: _n('Host disabled', 'Hosts disabled', $updated);
		$messageFailed = $enable
			? _n('Cannot enable host', 'Cannot enable hosts', $updated)
			: _n('Cannot disable host', 'Cannot disable hosts', $updated);

		show_messages($result, $messageSuccess, $messageFailed);
	}
}

/*
 * Display
 */
if (hasRequest('form')) {
	$data = [
		// Common & auxiliary
		'form' => getRequest('form', ''),
		'hostid' => getRequest('hostid', 0),
		'clone_hostid' => getRequest('clone_hostid', 0),
		'flags' => getRequest('flags', ZBX_FLAG_DISCOVERY_NORMAL),

		// Host
		'host' => getRequest('host', ''),
		'visiblename' => getRequest('visiblename', ''),
		'interfaces' => getRequest('interfaces', []),
		'mainInterfaces' => getRequest('mainInterfaces', []),
		'description' => getRequest('description', ''),
		'proxy_hostid' => getRequest('proxy_hostid', 0),
		'status' => getRequest('status', HOST_STATUS_NOT_MONITORED),

		// Templates
		'templates' => getRequest('templates', []),
		'add_templates' => [],
		'clear_templates' => getRequest('clear_templates', []),
		'original_templates' => [],
		'linked_templates' => [],
		'parent_templates' => [],

		// IPMI
		'ipmi_authtype' => getRequest('ipmi_authtype', IPMI_AUTHTYPE_DEFAULT),
		'ipmi_privilege' => getRequest('ipmi_privilege', IPMI_PRIVILEGE_USER),
		'ipmi_username' => getRequest('ipmi_username', ''),
		'ipmi_password' => getRequest('ipmi_password', ''),

		// Tags
		'tags' => $tags,

		// Macros
		'macros' => $macros,
		'show_inherited_macros' => getRequest('show_inherited_macros', 0),

		// Host inventory
		'inventory_mode' => getRequest('inventory_mode', CSettingsHelper::get(CSettingsHelper::DEFAULT_INVENTORY_MODE)),
		'host_inventory' => getRequest('host_inventory', []),
		'inventory_items' => [],

		// Encryption
		'tls_connect' => getRequest('tls_connect', HOST_ENCRYPTION_NONE),
		'tls_accept' => getRequest('tls_accept', HOST_ENCRYPTION_NONE),
		'tls_issuer' => getRequest('tls_issuer', ''),
		'tls_subject' => getRequest('tls_subject', ''),
		'tls_psk_identity' => getRequest('tls_psk_identity', ''),
		'tls_psk' => getRequest('tls_psk', ''),
		'psk_edit_mode' => getRequest('psk_edit_mode', 1),

		// Valuemap
		'valuemaps' => array_values(getRequest('valuemaps', []))
	];

	if (!hasRequest('form_refresh')) {
		if ($data['hostid'] != 0) {
			$dbHosts = API::Host()->get([
				'output' => ['hostid', 'proxy_hostid', 'host', 'name', 'status', 'ipmi_authtype', 'ipmi_privilege',
					'ipmi_username', 'ipmi_password', 'flags', 'description', 'tls_connect', 'tls_accept', 'tls_issuer',
					'tls_subject', 'inventory_mode'
				],
				'selectGroups' => ['groupid'],
				'selectParentTemplates' => ['templateid'],
				'selectMacros' => ['hostmacroid', 'macro', 'value', 'description', 'type'],
				'selectDiscoveryRule' => ['itemid', 'name'],
				'selectHostDiscovery' => ['parent_hostid'],
				'selectInventory' => API_OUTPUT_EXTEND,
				'selectTags' => ['tag', 'value'],
				'selectValueMaps' => ['valuemapid', 'name', 'mappings'],
				'hostids' => [$data['hostid']]
			]);
			$dbHost = reset($dbHosts);

			$data['flags'] = $dbHost['flags'];
			if ($data['flags'] == ZBX_FLAG_DISCOVERY_CREATED) {
				$data['discoveryRule'] = $dbHost['discoveryRule'];
				$data['hostDiscovery'] = $dbHost['hostDiscovery'];
			}

			// Host
			$data['host'] = $dbHost['host'];
			$data['visiblename'] = $dbHost['name'];
			$data['interfaces'] = API::HostInterface()->get([
				'output' => API_OUTPUT_EXTEND,
				'selectItems' => ['itemid'],
				'hostids' => [$data['hostid']],
				'sortfield' => 'interfaceid'
			]);
			$data['description'] = $dbHost['description'];
			$data['proxy_hostid'] = $dbHost['proxy_hostid'];
			$data['status'] = $dbHost['status'];

			// Templates
			$data['templates'] = zbx_objectValues($dbHost['parentTemplates'], 'templateid');
			$data['original_templates'] = array_combine($data['templates'], $data['templates']);

			// IPMI
			$data['ipmi_authtype'] = $dbHost['ipmi_authtype'];
			$data['ipmi_privilege'] = $dbHost['ipmi_privilege'];
			$data['ipmi_username'] = $dbHost['ipmi_username'];
			$data['ipmi_password'] = $dbHost['ipmi_password'];

			// Tags
			$data['tags'] = $dbHost['tags'];

			// Macros
			$data['macros'] = $dbHost['macros'];

			// Interfaces
			foreach ($data['interfaces'] as &$interface) {
				$interface['items'] = (bool) $interface['items'];
			}
			unset($interface);

			// Host inventory
			$data['inventory_mode'] = $dbHost['inventory_mode'];
			$data['host_inventory'] = $dbHost['inventory'];

			// Encryption
			$data['tls_connect'] = $dbHost['tls_connect'];
			$data['tls_accept'] = $dbHost['tls_accept'];
			$data['tls_issuer'] = $dbHost['tls_issuer'];
			$data['tls_subject'] = $dbHost['tls_subject'];

			if ($dbHost['tls_connect'] == HOST_ENCRYPTION_PSK || ($dbHost['tls_accept'] & HOST_ENCRYPTION_PSK)) {
				$data['psk_edit_mode'] = 0;
			}

			// display empty visible name if equal to host name
			if ($data['host'] === $data['visiblename']) {
				$data['visiblename'] = '';
			}

			// Valuemap
			CArrayHelper::sort($dbHost['valuemaps'], ['name']);
			$data['valuemaps'] = array_values($dbHost['valuemaps']);

			$groups = zbx_objectValues($dbHost['groups'], 'groupid');
		}
		else {
			$groups = getRequest('groupids', []);

			$data['status'] = HOST_STATUS_MONITORED;
		}
	}
	else {
		if ($data['hostid'] != 0) {
			$dbHosts = API::Host()->get([
				'output' => ['flags'],
				'selectParentTemplates' => ['templateid'],
				'selectDiscoveryRule' => ['itemid', 'name'],
				'selectHostDiscovery' => ['parent_hostid'],
				'hostids' => [$data['hostid']]
			]);
			$dbHost = reset($dbHosts);

			$data['flags'] = $dbHost['flags'];
			if ($data['flags'] == ZBX_FLAG_DISCOVERY_CREATED) {
				$data['discoveryRule'] = $dbHost['discoveryRule'];
				$data['hostDiscovery'] = $dbHost['hostDiscovery'];
			}

			$templateids = zbx_objectValues($dbHost['parentTemplates'], 'templateid');
			$data['original_templates'] = array_combine($templateids, $templateids);
		}

		foreach ([INTERFACE_TYPE_AGENT, INTERFACE_TYPE_SNMP, INTERFACE_TYPE_JMX, INTERFACE_TYPE_IPMI] as $type) {
			if (array_key_exists($type, $data['mainInterfaces'])) {
				$interfaceid = $data['mainInterfaces'][$type];
				$data['interfaces'][$interfaceid]['main'] = '1';
			}
		}
		$data['interfaces'] = array_values($data['interfaces']);

		$groups = getRequest('groups', []);
	}

	$data['readonly'] = ($data['flags'] == ZBX_FLAG_DISCOVERY_CREATED);

	if ($data['hostid'] != 0) {
		// get items that populate host inventory fields
		$data['inventory_items'] = API::Item()->get([
			'output' => ['inventory_link', 'itemid', 'hostid', 'name', 'key_'],
			'hostids' => [$dbHost['hostid']],
			'filter' => ['inventory_link' => array_keys(getHostInventories())]
		]);
		$data['inventory_items'] = zbx_toHash($data['inventory_items'], 'inventory_link');
		$data['inventory_items'] = CMacrosResolverHelper::resolveItemNames($data['inventory_items']);
	}

	if ($data['flags'] == ZBX_FLAG_DISCOVERY_CREATED) {
		if ($data['proxy_hostid'] != 0) {
			$data['proxies'] = API::Proxy()->get([
				'output' => ['host'],
				'proxyids' => [$data['proxy_hostid']],
				'preservekeys' => true
			]);
		}
		else {
			$data['proxies'] = [];
		}
	}
	else {
		$data['proxies'] = API::Proxy()->get([
			'output' => ['host'],
			'preservekeys' => true
		]);
		order_result($data['proxies'], 'host');
	}

	foreach ($data['proxies'] as &$proxy) {
		$proxy = $proxy['host'];
	}
	unset($proxy);

	// tags
	if (!$data['tags']) {
		$data['tags'][] = ['tag' => '', 'value' => ''];
	}
	else {
		CArrayHelper::sort($data['tags'], ['tag', 'value']);
	}

	// Add inherited macros to host macros.
	if ($data['show_inherited_macros']) {
		$data['macros'] = mergeInheritedMacros($data['macros'], getInheritedMacros(
			array_merge($data['templates'], getRequest('add_templates', []))
		));
	}

	// Sort only after inherited macros are added. Otherwise the list will look chaotic.
	$data['macros'] = array_values(order_macros($data['macros'], 'macro'));

	if (!$data['macros'] && !$data['readonly']) {
		$macro = ['macro' => '', 'value' => '', 'description' => '', 'type' => ZBX_MACRO_TYPE_TEXT];

		if ($data['show_inherited_macros']) {
			$macro['inherited_type'] = ZBX_PROPERTY_OWN;
		}

		$data['macros'][] = $macro;
	}

	$groupids = [];

	foreach ($groups as $group) {
		if (is_array($group) && array_key_exists('new', $group)) {
			continue;
		}

		$groupids[] = $group;
	}

	// Groups with R and RW permissions.
	$groups_all = $groupids
		? API::HostGroup()->get([
			'output' => ['name'],
			'groupids' => $groupids,
			'preservekeys' => true
		])
		: [];

	// Groups with RW permissions.
	$groups_rw = $groupids && (CWebUser::getType() != USER_TYPE_SUPER_ADMIN)
		? API::HostGroup()->get([
			'output' => [],
			'groupids' => $groupids,
			'editable' => true,
			'preservekeys' => true
		])
		: [];

	$data['groups_ms'] = [];

	// Prepare data for multiselect.
	foreach ($groups as $group) {
		if (is_array($group) && array_key_exists('new', $group)) {
			$data['groups_ms'][] = [
				'id' => $group['new'],
				'name' => $group['new'].' ('._x('new', 'new element in multiselect').')',
				'isNew' => true
			];
		}
		elseif (array_key_exists($group, $groups_all)) {
			$data['groups_ms'][] = [
				'id' => $group,
				'name' => $groups_all[$group]['name'],
				'disabled' => (CWebUser::getType() != USER_TYPE_SUPER_ADMIN) && !array_key_exists($group, $groups_rw)
			];
		}
	}
	CArrayHelper::sort($data['groups_ms'], ['name']);

	// Add already linked and new templates.
	$request_add_templates = getRequest('add_templates', []);

	if ($data['templates'] || $request_add_templates) {
		$templates = API::Template()->get([
			'output' => ['templateid', 'name'],
			'templateids' => array_merge($data['templates'], $request_add_templates),
			'preservekeys' => true
		]);

		$data['linked_templates'] = array_intersect_key($templates, array_flip($data['templates']));
		CArrayHelper::sort($data['linked_templates'], ['name']);

		$data['add_templates'] = array_intersect_key($templates, array_flip($request_add_templates));

		foreach ($data['add_templates'] as &$template) {
			$template = CArrayHelper::renameKeys($template, ['templateid' => 'id']);
		}
		unset($template);

		if ($data['templates']) {
			$data['writable_templates'] = API::Template()->get([
				'output' => ['templateid'],
				'templateids' => $data['templates'],
				'editable' => true,
				'preservekeys' => true
			]);
		}
	}

	// This data is used in common.template.edit.js.php.
	$data['macros_tab'] = [
		'linked_templates' => array_map('strval', array_keys($data['linked_templates'])),
		'add_templates' => array_map('strval', array_keys($data['add_templates']))
	];

	$data['allowed_ui_conf_templates'] = CWebUser::checkAccess(CRoleHelper::UI_CONFIGURATION_TEMPLATES);

	$hostView = new CView('configuration.host.edit', $data);
}
else {
	$sortField = getRequest('sort', CProfile::get('web.'.$page['file'].'.sort', 'name'));
	$sortOrder = getRequest('sortorder', CProfile::get('web.'.$page['file'].'.sortorder', ZBX_SORT_UP));

	CProfile::update('web.'.$page['file'].'.sort', $sortField, PROFILE_TYPE_STR);
	CProfile::update('web.'.$page['file'].'.sortorder', $sortOrder, PROFILE_TYPE_STR);

	// Get host groups.
	$filter['groups'] = $filter['groups']
		? CArrayHelper::renameObjectsKeys(API::HostGroup()->get([
			'output' => ['groupid', 'name'],
			'groupids' => $filter['groups'],
			'editable' => true,
			'preservekeys' => true
		]), ['groupid' => 'id'])
		: [];

	$filter_groupids = $filter['groups'] ? array_keys($filter['groups']) : null;
	if ($filter_groupids) {
		$filter_groupids = getSubGroups($filter_groupids);
	}

	// Get templates.
	$filter['templates'] = $filter['templates']
		? CArrayHelper::renameObjectsKeys(API::Template()->get([
			'output' => ['templateid', 'name'],
			'templateids' => $filter['templates'],
			'preservekeys' => true
		]), ['templateid' => 'id'])
		: [];

	switch ($filter['monitored_by']) {
		case ZBX_MONITORED_BY_ANY:
			$proxyids = null;
			break;

		case ZBX_MONITORED_BY_PROXY:
			$proxyids = $filter['proxyids']
				? $filter['proxyids']
				: array_keys(API::Proxy()->get([
					'output' => [],
					'preservekeys' => true
				]));
			break;

		case ZBX_MONITORED_BY_SERVER:
			$proxyids = 0;
			break;
	}

	// Select hosts.
	$limit = CSettingsHelper::get(CSettingsHelper::SEARCH_LIMIT) + 1;
	$hosts = API::Host()->get([
		'output' => ['hostid', $sortField],
		'evaltype' => $filter['evaltype'],
		'tags' => $filter['tags'],
		'groupids' => $filter_groupids,
		'templateids' => $filter['templates'] ? array_keys($filter['templates']) : null,
		'editable' => true,
		'sortfield' => $sortField,
		'limit' => $limit,
		'search' => [
			'name' => ($filter['host'] === '') ? null : $filter['host'],
			'ip' => ($filter['ip'] === '') ? null : $filter['ip'],
			'dns' => ($filter['dns'] === '') ? null : $filter['dns']
		],
		'filter' => [
			'port' => ($filter['port'] === '') ? null : $filter['port']
		],
		'proxyids' => $proxyids
	]);

	order_result($hosts, $sortField, $sortOrder);

	// pager
	if (hasRequest('page')) {
		$page_num = getRequest('page');
	}
	elseif (isRequestMethod('get') && !hasRequest('cancel')) {
		$page_num = 1;
	}
	else {
		$page_num = CPagerHelper::loadPage($page['file']);
	}

	CPagerHelper::savePage($page['file'], $page_num);

	$pagingLine = CPagerHelper::paginate($page_num, $hosts, $sortOrder, new CUrl('hosts.php'));

	$hosts = API::Host()->get([
		'output' => API_OUTPUT_EXTEND,
		'selectParentTemplates' => ['templateid', 'name'],
		'selectInterfaces' => API_OUTPUT_EXTEND,
		'selectItems' => API_OUTPUT_COUNT,
		'selectDiscoveries' => API_OUTPUT_COUNT,
		'selectTriggers' => API_OUTPUT_COUNT,
		'selectGraphs' => API_OUTPUT_COUNT,
		'selectApplications' => API_OUTPUT_COUNT,
		'selectHttpTests' => API_OUTPUT_COUNT,
		'selectDiscoveryRule' => ['itemid', 'name'],
		'selectHostDiscovery' => ['ts_delete'],
		'selectTags' => ['tag', 'value'],
		'hostids' => zbx_objectValues($hosts, 'hostid'),
		'preservekeys' => true
	]);
	order_result($hosts, $sortField, $sortOrder);

	// selecting linked templates to templates linked to hosts
	$templateids = [];

	foreach ($hosts as $host) {
		$templateids = array_merge($templateids, zbx_objectValues($host['parentTemplates'], 'templateid'));
	}

	$templateids = array_keys(array_flip($templateids));

	$templates = API::Template()->get([
		'output' => ['templateid', 'name'],
		'selectParentTemplates' => ['templateid', 'name'],
		'templateids' => $templateids,
		'preservekeys' => true
	]);

	// selecting writable templates IDs
	$writable_templates = [];
	if ($templateids) {
		foreach ($templates as $template) {
			$templateids = array_merge($templateids, zbx_objectValues($template['parentTemplates'], 'templateid'));
		}

		$writable_templates = API::Template()->get([
			'output' => ['templateid'],
			'templateids' => array_keys(array_flip($templateids)),
			'editable' => true,
			'preservekeys' => true
		]);
	}

	// Get proxy host IDs that are not 0 and maintenance IDs.
	$proxyHostIds = [];
	$maintenanceids = [];

	foreach ($hosts as &$host) {
		// Sort interfaces to be listed starting with one selected as 'main'.
		CArrayHelper::sort($host['interfaces'], [
			['field' => 'main', 'order' => ZBX_SORT_DOWN]
		]);

		if ($host['proxy_hostid']) {
			$proxyHostIds[$host['proxy_hostid']] = $host['proxy_hostid'];
		}

		if ($host['status'] == HOST_STATUS_MONITORED && $host['maintenance_status'] == HOST_MAINTENANCE_STATUS_ON) {
			$maintenanceids[$host['maintenanceid']] = true;
		}
	}
	unset($host);

	$proxies = [];
	if ($proxyHostIds) {
		$proxies = API::Proxy()->get([
			'proxyids' => $proxyHostIds,
			'output' => ['host'],
			'preservekeys' => true
		]);
	}

	// Prepare data for multiselect and remove unexisting proxies.
	$proxies_ms = [];
	if ($filter['proxyids']) {
		$filter_proxies = API::Proxy()->get([
			'output' => ['proxyid', 'host'],
			'proxyids' => $filter['proxyids']
		]);

		$proxies_ms = CArrayHelper::renameObjectsKeys($filter_proxies, ['proxyid' => 'id', 'host' => 'name']);
	}

	$db_maintenances = [];

	if ($maintenanceids) {
		$db_maintenances = API::Maintenance()->get([
			'output' => ['name', 'description'],
			'maintenanceids' => array_keys($maintenanceids),
			'preservekeys' => true
		]);
	}

	$data = [
		'hosts' => $hosts,
		'paging' => $pagingLine,
		'page' => $page_num,
		'filter' => $filter,
		'sortField' => $sortField,
		'sortOrder' => $sortOrder,
		'templates' => $templates,
		'maintenances' => $db_maintenances,
		'writable_templates' => $writable_templates,
		'proxies' => $proxies,
		'proxies_ms' => $proxies_ms,
		'profileIdx' => 'web.hosts.filter',
		'active_tab' => CProfile::get('web.hosts.filter.active', 1),
		'tags' => makeTags($hosts, true, 'hostid', ZBX_TAG_COUNT_DEFAULT, $filter['tags']),
		'config' => [
			'max_in_table' => CSettingsHelper::get(CSettingsHelper::MAX_IN_TABLE)
		],
		'allowed_ui_conf_templates' => CWebUser::checkAccess(CRoleHelper::UI_CONFIGURATION_TEMPLATES)
	];

	$hostView = new CView('configuration.host.list', $data);
}

echo $hostView->getOutput();

require_once dirname(__FILE__).'/include/page_footer.php';
