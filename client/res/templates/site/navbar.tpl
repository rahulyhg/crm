<style>
    
</style>
<div class="navbar navbar-inverse" role="navigation">
    <div class="navbar-header">
        <a class="navbar-brand nav-link" href="#"><img src="{{basePath}}client/img/samex_logo_small.png" class="logo"><span class="home-icon glyphicon glyphicon-th-large" title="{{translate 'Home'}}"></span></a>
        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-body">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
    </div>

    <div class="collapse navbar-collapse navbar-body">
        <ul class="nav navbar-nav tabs">
            {{#each tabDefsList}}
            {{#unless isInMore}}
            <li data-name="{{name}}" class="not-in-more"><a href="{{link}}" class="nav-link"><i class="glyphicon glyphicon-{{name}}"></i><span class="full-label">{{label}}</span><span class="short-label" title="{{label}}">{{shortLabel}}</span></a></li>
            {{/unless}}
            {{/each}}
            <li class="dropdown more" style="display:none;">
                <a id="nav-more-tabs-dropdown" class="dropdown-toggle" data-toggle="dropdown" href="#"><span class="more-label">{{translate 'More'}} <b class="caret"></b></span><span class="glyphicon glyphicon glyphicon-option-horizontal more-icon"></span></a>
                <ul class="dropdown-menu" role="menu" aria-labelledby="nav-more-tabs-dropdown">
                {{#each tabDefsList}}
                {{#if isInMore}}
                    <li data-name="{{name}}" class="in-more"><a href="{{link}}" class="nav-link"><span class="full-label">{{label}}</span></a></li>
                {{/if}}
                {{/each}}
                </ul>
            </li>
            <a class="minimizer" href="javascript:" style="float:right;">
                <span class="glyphicon glyphicon glyphicon-menu-right right"></span>
                <span class="glyphicon glyphicon glyphicon-menu-left left"></span>
            </a>
        </ul>
        <ul class="nav navbar-nav navbar-right">
            <li class="nav navbar-nav navbar-form global-search-container">
                Welcome {{userName}}!
                &nbsp;&nbsp;
                <img class="avatar img-circle" width="20" src="{{basePath}}?entryPoint=avatar&size=small&id={{userId}}&t=" />
                &nbsp;&nbsp;
                {{{globalSearch}}}
            </li>
            {{#if enableQuickCreate}}
            <li class="dropdown hidden-xs quick-create-container">
                <a id="nav-quick-create-dropdown" class="dropdown-toggle" data-toggle="dropdown" href="#"><i class="glyphicon glyphicon-plus" style="padding-right: 5px;"></i> Quick Create</a>
                <ul class="dropdown-menu" role="menu" aria-labelledby="nav-quick-create-dropdown">
                    <li class="dropdown-header">{{translate 'Create'}}</li>
                    {{#each quickCreateList}}
                    <li><a href="#{{./this}}/create" data-name="{{./this}}" data-action="quick-create" title="Create">{{translate this category='scopeNames'}}</a></li>
                    {{/each}}
                </ul>
            </li>
            {{/if}}
			<li class="dropdown notifications-badge-container">
                {{{notificationsBadge}}}
            </li>
            <li class="dropdown menu-container">
                <a id="nav-menu-dropdown" class="dropdown-toggle" data-toggle="dropdown" href="#" title="{{translate 'Menu'}}"><span class="glyphicon glyphicon-cog" style="padding-right: 5px;"></span> Settings</a>
                <ul class="dropdown-menu" role="menu" aria-labelledby="nav-menu-dropdown">
                    <li><a href="#User/view/{{userId}}" class="nav-link">My Profile</a></li>
                    <li class="divider"></li>
					<li><a href="#Preferences" class="nav-link">Set Preferences</a></li>
					<li><a href="#EmailAccount" class="nav-link">Setup Email</a></li>
					<li><a href="#EmailTemplate" class="nav-link">Customize Email Templates</a></li>
                </ul>
            </li>
            <li class="logout-container">
                <a href="#logout" class="nav-link"><i class="glyphicon glyphicon-share-alt" style="padding-right: 5px;"></i>Log Out</a>
            </li>
        </ul>
        <a class="minimizer" href="javascript:">
            <span class="glyphicon glyphicon glyphicon-menu-right right"></span>
            <span class="glyphicon glyphicon glyphicon-menu-left left"></span>
        </a>
    </div>
</div>
