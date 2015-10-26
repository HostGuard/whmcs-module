<?php
function hostguard_configoptions() {
	$configarray = array(
		'plan' => array(
			'FriendlyName' => 'Plan',
			'Type' => 'text',
			'Size' => '25',
			'Description' => 'The plan created in HostGuard'
		), // 1
		'template_id' => array(
			'FriendlyName' => 'Default Template ID',
			'Type' => 'text',
			'Size' => '25',
			'Description' => 'Default template used if there is no config option for templates'
		), // 2
		'location' => array(
			'FriendlyName' => 'Default Location ID',
			'Type' => 'text',
			'Size' => '25',
			'Description' => 'Default location used if there is no config option for locations'
		), // 3
		'controller' => array(
			'FriendlyName' => 'Controller',
			'Type' => 'dropdown',
			'Options' => 'libopenvz,libkvm,libxen',
			'Description' => 'Virtualization Controller',
            'Default' => 'libopenvz'
		), // 4
		'powercontrol' => array(
			'FriendlyName' => 'Power Control',
			'Type' => 'dropdown',
			'Options' => 'enable,disable',
			'Description' => 'Enable/disable power control options in client area',
            'Default' => 'enable'
		), // 5
		'reinstall' => array(
			'FriendlyName' => 'Reinstall',
			'Type' => 'dropdown',
			'Options' => 'enable,disable',
			'Description' => 'Enable/disable OS Reinstall in client area',
            'Default' => 'enable'
		), // 6
		'password' => array(
			'FriendlyName' => 'Password Change',
			'Type' => 'dropdown',
			'Options' => 'enable,disable',
			'Description' => 'Enable/disable root password and VNC password change in client area',
            'Default' => 'enable'
		), // 7
		'snapshots' => array(
			'FriendlyName' => 'Snapshots',
			'Type' => 'dropdown',
			'Options' => 'enable,disable',
			'Description' => 'Enable/disable snapshots in client area',
            'Default' => 'enable'
		), // 8
		'vpssettings' => array(
			'FriendlyName' => 'VPS Settings',
			'Type' => 'dropdown',
			'Options' => 'enable,disable',
			'Description' => 'Enable/disable VPS settings update in client area',
            'Default' => 'enable'
		), // 9
        'novnc' => array(
            'FriendlyName' => 'noVNC VNC Console',
            'Type' => 'dropdown',
            'Options' => 'enable,disable',
            'Description' => 'Enable/disable noVNC VNC console in client area',
            'Default' => 'enable'
        ), // 10
	);
	return $configarray;
}
function hostguard_makecall($params, $function, $fields) {
	if (!$params['server']) {
		return 'Product is not assigned to a server';
	}
	if (empty($params['serverhostname'])) {
		$params['serverhostname'] = $params['serverip'];
	}
	if ($params['serversecure']) {
		$requrl = 'https://' . $params['serverhostname'] . '/api/' . $function;
	} else {
		$requrl = 'http://' . $params['serverhostname'] . '/api/' . $function;
	}
	$fields['id'] = $params['serverusername'];
	$fields['key'] = $params['serverpassword'];
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $requrl);
	curl_setopt($ch, CURLOPT_POST, count($fields));
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    $result = curl_exec($ch);
    logmodulecall('hostguard', $function, $fields, $result);
	return json_decode($result, true);
}
function hostguard_createaccount($params) {
	$function = 'user/create';
	$fields = array(
		'username' => $params['clientsdetails']['email'],
		'password' => $params['password'],
		'firstname' => $params['clientsdetails']['firstname'],
		'lastname' => $params['clientsdetails']['lastname']
	);
	$result = hostguard_makecall($params, $function, $fields);
	if (!isset($result['id']) || empty($result['id'])) {
		return 'A user ID was not returned';
	}
	$user = $result['id'];
	if (isset($params['configoptions']['Template']) && !empty($params['configoptions']['Template'])) {
		$params['configoption2'] = $params['configoptions']['Template'];
	}
	if (isset($params['configoptions']['Location']) && !empty($params['configoptions']['Location'])) {
		$params['configoption3'] = $params['configoptions']['Location'];
	}
	$function = $params['configoption4'] . '/create';
	$fields = array(
		'user' => $user,
		'hostname' => $params['domain'],
		'location' => $params['configoption3'],
		'template_id' => $params['configoption2'],
		'ip' => 'automatic',
		'plan' => $params['configoption1'],
		'password' => $params['password']
	);
	$result = hostguard_makecall($params, $function, $fields);
	if (!isset($result['ctid'])) {
		$result = $result['info'];
	} else {
		$query = select_query('tblcustomfields', 'id', array(
			'relid' => $params['pid'],
			'fieldname' => 'vserverid'
		));
		$field = mysql_fetch_assoc($query);
		update_query('tblcustomfieldsvalues', array(
			'value' => $result['ctid']
		), array(
			'fieldid' => $field['id'],
			'relid' => $params['serviceid']
		));
		update_query('tblhosting', array(
			'dedicatedip' => $result['ip_address'],
			'username' => 'root'
		), array(
			'id' => $params['serviceid']
		));
		$result = "success";
	}
	return $result;
}
function hostguard_terminateaccount($params) {
	$function = 'server/destroy';
	$fields = array(
        'ctid' => $params['customfields']['ctid'],
		'vserverid' => $params['customfields']['vserverid']
	);
    if (intval($fields['ctid']) != 0) {
        unset($fields['vserverid']);
    } else {
        unset($fields['ctid']);
    }
	$result = hostguard_makecall($params, $function, $fields);
	if ($result['destroyed'] == true) {
		$result = "success";
		$query = select_query('tblcustomfields', 'id', array(
			'relid' => $params['pid'],
			'fieldname' => 'vserverid'
		));
		$field = mysql_fetch_assoc($query);
		update_query('tblcustomfieldsvalues', array(
			'value' => ''
		), array(
			'fieldid' => $field['id'],
			'relid' => $params['serviceid']
		));
		update_query('tblhosting', array(
			'dedicatedip' => '',
			'username' => ''
		), array(
			'id' => $params['serviceid']
		));
	} else {
		$result = "Could not terminate VPS...";
	}
	return $result;
}
function hostguard_suspendaccount($params) {
	$function = 'server/suspend';
	$fields = array(
        'ctid' => $params['customfields']['ctid'],
		'vserverid' => $params['customfields']['vserverid'],
		'reason' => $params['suspendreason']
	);
    if (intval($fields['ctid']) != 0) {
        unset($fields['vserverid']);
    } else {
        unset($fields['ctid']);
    }
	$result = hostguard_makecall($params, $function, $fields);
	if ($result['suspended'] == true) {
		$result = "success";
	} else {
		$result = "Could not suspend VPS...";
	}
	return $result;
}
function hostguard_unsuspendaccount($params) {
	$function = 'server/unsuspend';
	$fields = array(
        'ctid' => $params['customfields']['ctid'],
		'vserverid' => $params['customfields']['vserverid'],
		'reason' => 'Unsuspended via WHMCS Billing Panel'
	);
    if (intval($fields['ctid']) != 0) {
        unset($fields['vserverid']);
    } else {
        unset($fields['ctid']);
    }
	$result = hostguard_makecall($params, $function, $fields);
	if ($result['unsuspended'] == true) {
		$result = "success";
	} else {
		$result = "Could not unsuspend VPS...";
	}
	return $result;
}
function hostguard_changepackage($params) {
	$function = 'server/upgrade';
	$fields = array(
        'ctid' => $params['customfields']['ctid'],
		'plan' => $params['configoption1'],
		'vserverid' => $params['customfields']['vserverid']
	);
    if (intval($fields['ctid']) != 0) {
        unset($fields['vserverid']);
    } else {
        unset($fields['ctid']);
    }
	$result = hostguard_makecall($params, $function, $fields);
	if (!$result['error']) {
		$result = "success";
	} else {
		$result = $result['error'];
	}
	return $result;
}
function hostguard_reboot($params, $return = false) {
	$function = 'server/reboot';
	$fields = array(
		'ctid' => $params['customfields']['ctid'],
        'vserverid' => $params['customfields']['vserverid']
	);
    if (intval($fields['ctid']) != 0) {
        unset($fields['vserverid']);
    } else {
        unset($fields['ctid']);
    }
	$result = hostguard_makecall($params, $function, $fields);
    if ($return) {
        return $result;
    }
	if ($result['status'] != 'success') {
		return $result['statusmsg'];
	} else {
		return 'success';
	}
}
function hostguard_shutdown($params, $return = false) {
	$function = 'server/shutdown';
	$fields = array(
		'ctid' => $params['customfields']['ctid'],
        'vserverid' => $params['customfields']['vserverid']
	);
    if (intval($fields['ctid']) != 0) {
        unset($fields['vserverid']);
    } else {
        unset($fields['ctid']);
    }
	$result = hostguard_makecall($params, $function, $fields);
    if ($return) {
        return $result;
    }
	if ($result['status'] != 'success') {
		return $result['statusmsg'];
	} else {
		return 'success';
	}
}
function hostguard_poweroff($params, $return = false) {
	$function = 'server/poweroff';
	$fields = array(
		'ctid' => $params['customfields']['ctid'],
        'vserverid' => $params['customfields']['vserverid']
	);
    if (intval($fields['ctid']) != 0) {
        unset($fields['vserverid']);
    } else {
        unset($fields['ctid']);
    }
	$result = hostguard_makecall($params, $function, $fields);
    if ($return) {
        return $result;
    }
	if ($result['status'] != 'success') {
		return $result['statusmsg'];
	} else {
		return 'success';
	}
}
function hostguard_boot($params, $return = false) {
	$function = 'server/boot';
	$fields = array(
		'ctid' => $params['customfields']['ctid'],
        'vserverid' => $params['customfields']['vserverid']
	);
    if (intval($fields['ctid']) != 0) {
        unset($fields['vserverid']);
    } else {
        unset($fields['ctid']);
    }
	$result = hostguard_makecall($params, $function, $fields);
    if ($return) {
        return $result;
    }
    if ($result['status'] != 'success') {
        return $result;
    } else {
        return 'success';
    }
}
function hostguard_changepassword($params) {
	if (!$params['configoption7'] && strpos($_SERVER['REQUEST_URI'], 'clientarea') !== false) {
		return 'Password change is disabled';
	}
	$function = 'server/rootpassword';
	$fields = array(
		'ctid' => $params['customfields']['ctid'],
        'vserverid' => $params['customfields']['vserverid']
	);
    if (intval($fields['ctid']) != 0) {
        unset($fields['vserverid']);
    } else {
        unset($fields['ctid']);
    }
	$result = hostguard_makecall($params, $function, $fields);
	if ($result['status'] != 'success') {
		return $result['statusmsg'];
	} else {
		return 'success';
	}
}
function hostguard_clientarea($params) {
    $status = 'success';
	$statusmsg = 'success';
	if (isset($_REQUEST['hg-action'])) {
		if ($_REQUEST['hg-action'] == 'power' && isset($_REQUEST['a']) && $params['configoption5'] != 'disable') {
			if ($_REQUEST['a'] == 'shutdown') {
				$result = hostguard_shutdown($params, true);
				if ($result['status'] != 'success') {
                    $status = 'error';
					$statusmsg = $result['statusmsg'];
				} else {
					$statusmsg = 'VPS Shutdown';
				}
			} elseif ($_REQUEST['a'] == 'poweroff') {
				$result = hostguard_poweroff($params, true);
				if ($result['status'] != 'success') {
                    $status = 'error';
					$statusmsg = $result['statusmsg'];
				} else {
					$statusmsg = 'VPS Poweroff';
				}
			} elseif ($_REQUEST['a'] == 'reboot') {
				$result = hostguard_reboot($params, true);
				if ($result['status'] != 'success') {
                    $status = 'error';
					$statusmsg = $result['statusmsg'];
				} else {
					$statusmsg = 'VPS Reboot';
				}
			} elseif ($_REQUEST['a'] == 'boot') {
				$result = hostguard_boot($params, true);
				if ($result['status'] != 'success') {
                    $status = 'error';
					$statusmsg = $result['statusmsg'];
				} else {
					$statusmsg = 'VPS Boot';
				}
			}
		} elseif ($_REQUEST['hg-action'] == 'settings' && $params['configoption9'] != 'disable') {
            $fields = array(
                'ctid' => $params['customfields']['ctid'],
                'vserverid' => $params['customfields']['vserverid']
            );
            if (intval($fields['ctid']) != 0) {
                unset($fields['vserverid']);
            } else {
                unset($fields['ctid']);
            }
			$vpsdata = hostguard_makecall($params, 'server/index', $fields);
			$vpsdata['data']['kvm_apic'] = $_REQUEST['kvm_apic'];
			$vpsdata['data']['kvm_acpi'] = $_REQUEST['kvm_acpi'];
			$vpsdata['data']['kvm_pae'] = $_REQUEST['kvm_pae'];
			$vpsdata['data']['kvm_bootorder'] = $_REQUEST['kvm_bootorder'];
			$vpsdata['data']['kvm_nic_type'] = $_REQUEST['kvm_nic_type'];
			$vpsdata['data']['kvm_disk_type'] = $_REQUEST['kvm_disk_type'];
			$vpsdata['data']['kvm_iso'] = $_REQUEST['kvm_iso'];
			$result = hostguard_makecall($params, 'server/save', array(
				'ctid' => $params['customfields']['vserverid'],
				'data' => json_encode($vpsdata['data'])
			));
			if (!empty($_REQUEST['vncpassword']) && $params['configoption7'] != 'disable' && strlen($_REQUEST['vncpassword']) > 5) {
				hostguard_makecall($params, 'server/vncpassword', array(
					'ctid' => $params['customfields']['vserverid'],
					'password' => $_REQUEST['vncpassword']
				));
			}
            if ($result['status'] != 'success') {
                $status = 'error';
				$statusmsg = $result['statusmsg'];
			} else {
				$statusmsg = 'VPS Boot';
			}
			$statusmsg = 'VPS Settings updated';
		} elseif ($_REQUEST['hg-action'] == 'rebuild' && $params['configoption6'] != 'disable' && isset($_REQUEST['template'])) {
            $fields = array(
                'ctid' => $params['customfields']['ctid'],
                'vserverid' => $params['customfields']['vserverid'],
                'template' => $_REQUEST['template'],
				'password' => $params['password']
            );
            if (intval($fields['ctid']) != 0) {
                unset($fields['vserverid']);
            } else {
                unset($fields['ctid']);
            }
			$result = hostguard_makecall($params, 'server/reinstall', $fields);
			if ($result['status'] != 'success') {
                $status = 'error';
				$statusmsg = $result['statusmsg'];
			} else {
				$statusmsg = 'VPS Boot';
			}
		}
	}
    $fields = array(
        'ctid' => $params['customfields']['ctid'],
        'vserverid' => $params['customfields']['vserverid']
    );
    if (intval($fields['ctid']) != 0) {
        unset($fields['vserverid']);
    } else {
        unset($fields['ctid']);
    }
	$vpsdata = hostguard_makecall($params, 'server/index', $fields);
	if ($vpsdata['status'] != 'success') {
		return array(
			'templatefile' => 'hostguard-clientarea',
			'vars' => array(
				'status' => 'error',
				'statusmsg' => 'No data could be fetched'
			)
		);
	}
	return array(
		'templatefile' => 'hostguard-clientarea',
		'vars' => array(
			'status' => $status,
			'statusmsg' => $statusmsg,
			'params' => $params,
			'vpsdata' => $vpsdata
		)
	);
}
function hostguard_vncconsole($params) {
    $fields = array(
        'ctid' => $params['customfields']['ctid'],
        'vserverid' => $params['customfields']['vserverid']
    );
    if (intval($fields['ctid']) != 0) {
        unset($fields['vserverid']);
    } else {
        unset($fields['ctid']);
    }
    $vpsdata = hostguard_makecall($params, 'server/vncconsole', $fields);
    return array(
        'templatefile' => 'hostguard-novnc',
        'vars' => array(
            'result' => $vpsdata
        ),
    );
}
function hostguard_clientareacustombuttonarray($params) {
	if ($params['configoption10'] != 'disable') {
        return array('VNC Console' => 'vncconsole');
    }
}
function hostguard_admincustombuttonarray() {
	$buttonarray = array(
		'Reboot' => 'reboot',
		'Shutdown' => 'shutdown',
		'Power Off' => 'poweroff',
		'Boot' => 'boot'
	);
	return $buttonarray;
}
function hostguard_adminservicestabfields($params) {
	if (empty($params['customfields']['ctid']) && empty($params['customfields']['vserverid'])) {
		$statusmsg = 'No vserverid / ctid custom field value';
	} else {
        $fields = array(
            'ctid' => $params['customfields']['ctid'],
            'vserverid' => $params['customfields']['vserverid']
        );
        if (intval($fields['ctid']) != 0) {
            unset($fields['vserverid']);
        } else {
            unset($fields['ctid']);
        }
		$result = hostguard_makecall($params, 'server/index', $fields);
		if ($result['status'] != 'success') {
            if (isset($result['statusmsg'])) {
                $statusmsg = $result['statusmsg'];
            } else {
                $statusmsg = 'No data could be fetched';
            }
		} else {
			$statusmsg = '<div class="tablebg"><table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3"><tbody><tr><th>Attribute</th><th>Value</th></tr>';
			foreach ($result['data'] as $key => $value) {
				if (!empty($value) && $value != 0) {
					$statusmsg .= '<tr><td>' . htmlentities($key) . '</td><td>' . htmlentities($value) . '</td></tr>';
				}
			}
			$statusmsg .= '</tbody></table></div>';
		}
	}
	$adminarray = array(
		'VPS Details' => $statusmsg
	);
	return $adminarray;
}
function hostguard_testconnection($params) {
    $result = hostguard_makecall($params, 'auth/api_status', array());
    if ($result['status'] == 'success') {
        return array('success' => true);
    } elseif (!empty($result['statusmsg'])) {
        return array('error' => $result['statusmsg']);
    } else {
        return array('error' => 'Connection error');
    }
}
?>