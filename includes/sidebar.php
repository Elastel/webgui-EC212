    <ul class="navbar-nav sidebar sidebar-light d-none d-md-block accordion <?php echo (isset($toggleState)) ? $toggleState : null ; ?>" id="accordionSidebar">
        <!-- Divider -->
        <hr class="sidebar-divider my-0">
        <div class="row">
            <div class="col-xs ml-3 sidebar-brand-icon">
            <img src="app/img/<?php echo ( ($target != null) ? "$hostname.php" : "elastel.php"); ?>" class="navbar-logo" width="200" height="50">
            </div>
        </div>
        <li class="nav-item">
            <a class="nav-link" href="dashboard"><i class="fas fa-tachometer-alt fa-fw mr-2"></i><span class="nav-label"><?php echo _("Dashboard"); ?></span></a>
        </li>
        <li class="nav-item" id="page_network">
            <a class="nav-link navbar-toggle collapsed" id="network" href="#" data-toggle="collapse" data-target="#navbar-collapse-network">
                <i class="fas fa-network-wired fa-fw mr-2"></i>
                <span class="nav-label"><?php echo _("Network"); ?></a>
            </a>
            <div class="collapse navbar-collapse" id="navbar-collapse-network">
            <ul class="nav navbar-nav navbar-right">
                <li class="nav-item" name="wan" id="wan" ><a class="nav-link" href="network_conf"><?php echo _("WAN"); ?></a></li>
                <li class="nav-item" name="lan" id="lan" ><a class="nav-link" href="dhcpd_conf"><?php echo _("LAN"); ?></a></li>
                <li class="nav-item" name="wifi" id="wifi" ><a class="nav-link" href="hostapd_conf"><?php echo _("WiFi"); ?></a></li>
                <li class="nav-item" name="wifi_client" id="wifi_client" ><a class="nav-link" href="wpa_conf"><?php echo _("WiFi client"); ?></a></li>
                <li class="nav-item" name="online_detection" id="online_detection" ><a class="nav-link" href="detection_conf"><?php echo _("Online Detection"); ?></a></li>
                <?php if ($model == "EG500" || $model == "EG410" || $model == "ElastBox400") : ?>
                <li class="nav-item" name="lorawan" id="lorawan" ><a class="nav-link" href="lorawan_conf"><?php echo _("LoRaWan"); ?></a></li>
                <?php endif; ?>
                <?php if ($model == "EG500" || $model == "EG410" || $model == "ElastBox400") : ?>
                <li class="nav-item" name="firewall" id="firewall" ><a class="nav-link" href="firewall_conf"><?php echo _("Firewall"); ?></a></li>
                <?php endif; ?>
            </ul>
            </div>
        </li>
        <li class="nav-item" id="page_dct">
            <a class="nav-link navbar-toggle collapsed" id="dct" href="#" data-toggle="collapse" data-target="#navbar-collapse-dct">
                <i class="fas fa-exchange-alt fa-fw mr-2"></i>
                <span class="nav-label"><?php echo _("Data Collect"); ?></a>
            </a>
            <div class="collapse navbar-collapse" id="navbar-collapse-dct">
            <ul class="nav navbar-nav navbar-right">
                <li class="nav-item" name="dct_basic" id="dct_basic" ><a class="nav-link" href="basic_conf"><?php echo _("Basic"); ?></a></li>
                <li class="nav-item" name="interfaces" id="dct_interfaces"><a class="nav-link" href="interfaces_conf"><?php echo _("Interfaces"); ?></a></li>
                <li class="nav-item" name="modbus" id="dct_modbus"><a class="nav-link" href="modbus_conf"><?php echo _("Modbus Rules"); ?></a></li>
                <li class="nav-item" name="ascii" id="dct_ascii"><a class="nav-link" href="ascii_conf"><?php echo _("ASCII Rules"); ?></a></li>
                <li class="nav-item" name="s7" id="dct_s7"><a class="nav-link" href="s7_conf"><?php echo _("S7 Rules"); ?></a></li>
                    <li class="nav-item" name="fx" id="dct_fx"><a class="nav-link" href="fx_conf"><?php echo _("FX Rules"); ?></a></li>
                <li class="nav-item" name="mc" id="dct_mc"><a class="nav-link" href="mc_conf"><?php echo _("MC Rules"); ?></a></li>
                <li class="nav-item" name="iec104" id="dct_iec104"><a class="nav-link" href="iec104_conf"><?php echo _("IEC104 Rules"); ?></a></li>
                <li class="nav-item" name="dnp3_client" id="dct_dnp3_client"><a class="nav-link" href="dnp3cli_conf"><?php echo _("DNP3 Rules"); ?></a></li>
                <?php if ($model == "EG500" || $model == "EG410") : ?>
                <li class="nav-item" name="io" id="dct_io"><a class="nav-link" href="io_conf"><?php echo _("IO"); ?></a></li>
                <?php endif; ?>
                <li class="nav-item" name="opcua_client" id="dct_opcua_client"><a class="nav-link" href="opcuacli_conf"><?php echo _("OPCUA Rules"); ?></a></li>
                <?php if(isBinExists("baccli")) : ?>
                <li class="nav-item" name="bacnet_client" id="dct_bacnet_client"><a class="nav-link" href="baccli_conf"><?php echo _("BACnet Rules"); ?></a></li>
                <?php endif; ?>
                <li class="nav-item" name="server" id="dct_server"><a class="nav-link" href="server_conf"><?php echo _("Reporting Server"); ?></a></li>
                <li class="nav-item" name="modbus_slave" id="dct_modbus_slave"><a class="nav-link" href="modbus_slave"><?php echo _("Modbus Slave"); ?></a></li>
                <li class="nav-item" name="opcua" id="dct_opcua"><a class="nav-link" href="opcua"><?php echo _("OPCUA Server"); ?></a></li>
                <?php if(isBinExists("bacserv")) : ?>
                <li class="nav-item" name="bacnet" id="dct_bacnet"><a class="nav-link" href="bacnet"><?php echo _("BACnet Server"); ?></a></li>
                <?php endif; ?>
                <li class="nav-item" name="dnp3" id="dct_dnp3"><a class="nav-link" href="dnp3"><?php echo _("DNP3 Server"); ?></a></li>
                <li class="nav-item" name="datadisplay" id="dct_datadisplay"><a class="nav-link" href="datadisplay"><?php echo _("Data Display"); ?></a></li>
            </ul>
            </div>
        </li>
        <li class="nav-item" id="page_remote">
            <a class="nav-link navbar-toggle collapsed" id="remote" href="#" data-toggle="collapse" data-target="#navbar-collapse-remote">
                <i class="fas fa-server fa-fw mr-2"></i>
                <span class="nav-label"><?php echo _("Remote Manage"); ?></a>
            </a>
            <div class="collapse navbar-collapse" id="navbar-collapse-remote">
            <ul class="nav navbar-nav navbar-right">
                <li class="nav-item" name="ddns" id="ddns"> <a class="nav-link" href="ddns"><?php echo _("DDNS"); ?></a></li>
                <li class="nav-item" name="macchina" id="macchina"> <a class="nav-link" href="macchina"><?php echo _("Macchina"); ?></a></li>
            </ul>
            </div>
        </li>
        <li class="nav-item" id="page_vpn">
            <a class="nav-link navbar-toggle collapsed" id="vpn" href="#" data-toggle="collapse" data-target="#navbar-collapse-vpn">
                <i class="fas fa-key fa-fw mr-2"></i>
                <span class="nav-label"><?php echo _("VPN"); ?></a>
            </a>
            <div class="collapse navbar-collapse" id="navbar-collapse-vpn">
            <ul class="nav navbar-nav navbar-right">
                <li class="nav-item" name="openvpn" id="openvpn"> <a class="nav-link" href="openvpn"><?php echo _("OpenVPN"); ?></a></li>
                <?php if ($model != "EG324L") : ?>
                <li class="nav-item" name="wireguard" id="wireguard"> <a class="nav-link" href="wireguard"><?php echo _("WireGuard"); ?></a></li>
                <?php endif; ?>
            </ul>
            </div>
        </li>
        <li class="nav-item" id="page_services">
            <a class="nav-link navbar-toggle collapsed" id="services" href="#" data-toggle="collapse" data-target="#navbar-collapse-services">
                <i class="fas fa-cube fa-fw mr-2"></i>
                <span class="nav-label"><?php echo _("Services"); ?></a>
            </a>
            <div class="collapse navbar-collapse" id="navbar-collapse-services">
            <ul class="nav navbar-nav navbar-right">
                <li class="nav-item" name="terminal" id="terminal"> <a class="nav-link" href="terminal"><?php echo _("Terminal"); ?></a></li>
                <?php if ($model == "EG500" || $model == "EG410") : ?>
                <li class="nav-item" name="gps" id="gps"> <a class="nav-link" href="gps"><?php echo _("GPS Location"); ?></a></li>
                <?php endif; ?>
                <?php if(isBinExists("node-red")) : ?>
                <li class="nav-item" name="nodered" id="nodered"> <a class="nav-link" href="nodered"><?php echo _("Node Red"); ?></a></li>
                <?php endif; ?>
                <?php if(isBinExists("dockerd")) : ?>
                <li class="nav-item" name="docker" id="docker"> <a class="nav-link" href="docker"><?php echo _("Docker"); ?></a></li>
                <?php endif; ?>
                <?php if(isBinExists("router-mstp")) : ?>
                <li class="nav-item" name="bacnet_router" id="bacnet_router"> <a class="nav-link" href="bacnet_router"><?php echo _("BACnet Router"); ?></a></li>
                <?php endif; ?>
                <?php if(isBinExists("chirpstack")) : ?>
                <li class="nav-item" name="chirpstack" id="chirpstack"> <a class="nav-link" href="chirpstack"><?php echo _("Chirpstack"); ?></a></li>
                <?php endif; ?>
            </ul>
            </div>
        </li>
        <li class="nav-item" id="page_system">
            <a class="nav-link navbar-toggle collapsed" id="system" href="#" data-toggle="collapse" data-target="#navbar-collapse-system">
                <i class="fas fa-cogs fa-fw mr-2"></i>
                <span class="nav-label"><?php echo _("System"); ?></a>
            </a>
            <div class="collapse navbar-collapse" id="navbar-collapse-system">
            <ul class="nav navbar-nav navbar-right">
                <li class="nav-item" name="system_info" id="system_info"> <a class="nav-link" href="system_info"><?php echo _("System"); ?></a></li>
                <li class="nav-item" name="auth_conf" id="auth_conf"> <a class="nav-link" href="auth_conf"><?php echo _("Authentication"); ?></a></li>
                <li class="nav-item" name="backup_update" id="backup_update"> <a class="nav-link" href="backup_update"><?php echo _("Update/Restore"); ?></a></li>
            </ul>
            </div>
        </li>
        <?php if ($target == null) : ?>
        <li class="nav-item">
            <a class="nav-link" href="about"><i class="fas fa-info-circle fa-fw mr-2"></i><span class="nav-label"><?php echo _("About Elastel"); ?></a>
        </li>
        <?php endif; ?>
        <!-- Divider -->
        <hr class="sidebar-divider d-none d-md-block">
    </ul>