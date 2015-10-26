{if $status eq 'error'}
<div class="alert alert-danger"><strong>Error!</strong> {$statusmsg}</div>
{else}
{if $statusmsg neq 'success'}
<div class="alert alert-success"><strong>Success!</strong> {$statusmsg}</div>
{/if}
{*
<ul>
	{foreach from=$vpsdata.data key=k item=v}
	<li>{$k}: {$v}</li>
	{/foreach}
</ul>
*}
{if $params.configoption5 neq disable}
<h2>Power Control</h2>
<a href="clientarea.php?action=productdetails&id={$smarty.get.id}&hg-action=power&a=shutdown" class="btn btn-danger"><i class="icon-stop icon-white"></i> Shutdown</a>
<a href="clientarea.php?action=productdetails&id={$smarty.get.id}&hg-action=power&a=poweroff" class="btn btn-default"><i class="icon-remove-circle icon-white"></i> Power Off</a>
<a href="clientarea.php?action=productdetails&id={$smarty.get.id}&hg-action=power&a=reboot" class="btn btn-info"><i class="icon-repeat icon-white"></i> Reboot</a>
<a href="clientarea.php?action=productdetails&id={$smarty.get.id}&hg-action=power&a=boot" class="btn btn-warning"><i class="icon-play icon-white"></i> Boot</a>
{/if}
{if $params.configoption10 neq disable}<a id="vnctoggle" class="btn btn-primary" href="clientarea.php?action=productdetails&id={$smarty.get.id}&modop=custom&a=vncconsole"><i class="icon-user icon-white"></i> VNC Console</a>{/if}

<h2>VPS Details</h2>

<table class="table table-bordered table-striped">
	<tbody>
		<tr>
			<td>Hostname</td>
			<td>{$vpsdata.data.hostname}</td>
		</tr>
		<tr>
			<td>Guaranteed Memory</td>
			<td>{$vpsdata.data.memory_guaranteed}</td>
		</tr>
		<tr>
			<td>Burstable Memory</td>
			<td>{$vpsdata.data.memory_burst}</td>
		</tr>
		<tr>
			<td>Disk Size</td>
			<td>{$vpsdata.data.disk_size}</td>
		</tr>
		<tr>
			<td>CPU Cores</td>
			<td>{$vpsdata.data.cpu_cores}</td>
		</tr>
		<tr>
			<td>Bandwidth Allowed</td>
			<td>{$vpsdata.data.bandwidth_allowed}</td>
		</tr>
		{if $params.configoption7 neq disable}
		<tr>
			<td>KVM Password</td>
			<td>{$vpsdata.data.kvm_password}</td>
		</tr>
		<tr>
			<td>KVM VNC Port</td>
			<td>{$vpsdata.data.kvm_vnc_port}</td>
		</tr>
		{/if}
		<tr>
			<td>MAC Address</td>
			<td>{$vpsdata.data.mac}</td>
		</tr>
	</tbody>
</table>
{if $params.configoption9 neq disable}
<h2>VPS Settings</h2>
<form method="post" action="{$systemurl}clientarea.php?action=productdetails&id={$smarty.get.id}">
	<input type="hidden" name="hg-action" value="settings">
	<table class="table table-bordered table-striped">
		<tbody>
			<tr>
				<td>KVM APIC</td>
				<td>
					<div class="radio-inline">
						<input type="radio" name="apic" value="1" {if $vpsdata.data.kvm_apic eq 1}checked{/if}> On
					</div>
					<div class="radio-inline">
						<input type="radio" name="apic" value="0" {if $vpsdata.data.kvm_apic eq 0}checked{/if}> Off
					</div>	
				</td>
			</tr>
			<tr>
				<td>KVM ACPI</td>
				<td>
					<div class="radio-inline">
						<input type="radio" name="acpi" value="1" {if $vpsdata.data.kvm_acpi eq 1}checked{/if}>On
					</div>
					<div class="radio-inline">
						<input type="radio" name="acpi" value="0" {if $vpsdata.data.kvm_acpi eq 0}checked{/if}>Off
					</div>
				</td>
			</tr>
			<tr>
				<td>KVM PAE</td>
				<td>
					<div class="radio-inline">
						<input type="radio" name="pae" value="1" {if $vpsdata.data.kvm_pae eq 1}checked{/if}>On
					</div>
					<div class="radio-inline">
						<input type="radio" name="pae" value="0" {if $vpsdata.data.kvm_pae eq 0}checked{/if}>Off
					</div>
				</td>
			</tr>
			<tr>
				<td>KVM Boot Options</td>
				<td>
					<select class="form-control">
					{foreach from=$vpsdata.options.boot_options key=k item=v}
					<option value="{$k}"{if $k eq $vpsdata.data.kvm_bootorder} selected{/if}>{$v}</option>
					{/foreach}
					</select>
				</td>
			</tr>
			<tr>
				<td>KVM NIC Options</td>
				<td>
					<select class="form-control">
					{foreach from=$vpsdata.options.nic_options key=k item=v}
					<option value="{$k}"{if $k eq $vpsdata.data.kvm_nic_type} selected{/if}>{$v}</option>
					{/foreach}
					</select>
				</td>
			</tr>
			<tr>
				<td>KVM Disk Options</td>
				<td>
					<select class="form-control">
					{foreach from=$vpsdata.options.disk_options key=k item=v}
					<option value="{$k}"{if $k eq $vpsdata.data.kvm_disk_type} selected{/if}>{$v}</option>
					{/foreach}
					</select>
				</td>
			</tr>
			<tr>
				<td>KVM ISO Options</td>
				<td>
					<select class="form-control">
					{foreach from=$vpsdata.options.iso_options key=k item=v}
					<option value="{$k}"{if $k eq $vpsdata.data.kvm_iso} selected{/if}>{$v}</option>
					{/foreach}
					</select>
				</td>
			</tr>
			{if $params.configoption7 neq disable}
			<tr>
				<td>VNC Password</td>
				<td><input type="password" class="form-control" name="vncpassword"></td>
			</tr>
			{/if}
			<tr>
				<td colspan="3"><button class="btn btn-large btn-primary" type="submit">Save Settings</button></td>
			</tr>
		</tbody>
	</table>
</form>
{/if}
{if $params.configoption8 neq disable}
<h2>Snapshots</h2>
<table class="table table-bordered table-striped">
	<tbody>
		{if count($vpsdata.snapshots) > 0}
		{foreach from=$vpsdata.snapshots key=k item=v}
		<tr>
			<td>{$k}</td>
			<td>{$v}</td>
		</tr>
		{/foreach}
		{else}
		<tr>
			<td>No data available</td>
		</tr>
		{/if}
	</tbody>
</table>
{/if}
{if $params.configoption6 neq disable}
<h2>Rebuild</h2>
{literal}
<a class="btn btn-large btn-primary" id="rebuildtoggle">Show Rebuild Options</a>
<script>
	$("#rebuildtoggle").click(function(){
	    $("#rebuildlist").show();
	    $("#rebuildtoggle").remove();
	});
</script>
{/literal}
<form method="post" action="{$systemurl}clientarea.php?action=productdetails&id={$smarty.get.id}" id="rebuildlist" style="display:none">
	<input type="hidden" name="hg-action" value="rebuild">
	<table class="table table-bordered table-striped">
		<tbody>
			<tr>
				<th>Name</th>
				<th>Type</th>
				<th>Select</th>
			</tr>
			{foreach from=$vpsdata.templates key=k item=v}
			<tr>
				<td>{$v.template_name}</td>
				<td>{$v.template_os}</td>
				<td><input type="radio" name="template" value="{$v.template_id}"></td>
			</tr>
			{/foreach}
			<tr>
				<td colspan="3"><button class="btn btn-large btn-primary" type="submit">Rebuild VPS</button></td>
			</tr>
		</tbody>
	</table>
</form>
{/if}
{/if}