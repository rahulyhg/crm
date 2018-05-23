<div class="row">
{{#unless readOnly}}
    
        <div class="col-md-2">
            <button class="btn btn-default btn-sm" type="button" data-action='editMergeField'>{{translate 'Edit'}}</button>
        </div>
    </div>
<div class="row">
    <div class="col-md-2">&nbsp;</div>
{{/unless}}

    <div class="col-md-9">
        {{translate 'Merge Field Tag' category='fields' scope="MailChimp"}} <span class="text-muted">&raquo;</span> {{{mergeFieldData.mergeFieldTag}}}

        <div class="field-list small" style="margin-top: 12px;">
            {{#if mergeFieldData.scopes}}
                {{#each mergeFieldData.scopes}}
                    {{#if ./this}}
                    {{translate @key category='scopeNames'}}
                    <div class="field-row cell form-group" data-scope="{{@key}}" data-field="{{./this}}">
                        <label class="control-label">{{translate ./this category='fields' scope=@key}}</label>
                        <div class="field-container field" data-field="{{./this}}"></div>
                    </div>
                    {{/if}}
                {{/each}}
            {{/if}}
        </div>
    </div>
</div>
