{{#if closeButton}}
<a href="javascript:" class="pull-right close" data-action="close" aria-hidden="true">×</a>
{{/if}}
<h4>{{header}}</h4>

{{#if notificationData.failed}}
<div class="cell form-group">
    <div class="field">
        <a href="#{{notificationData.entityType}}/view/{{notificationData.id}}" data-action="close">{{notificationData.entityName}}</a>
        {{translate 'failed synced with MailChimp' category='labels' scope='MailChimp'}}
    </div>

</div>
{{else}}
<div class="cell form-group">
    <div class="field">
        <a href="#{{notificationData.entityType}}/view/{{notificationData.id}}" data-action="close">{{notificationData.entityName}}</a>
        {{translate 'synced with MailChimp' category='labels' scope='MailChimp'}}
    </div>

</div>
<div class="cell cell-lastSynced form-group">
    <div class="field field-lastSynced">
        {{{lastSynced}}}
    </div>
</div>
{{/if}}
