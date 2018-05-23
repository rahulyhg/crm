
<div class="row">
    {{#unless readOnly}}
        <div class="col-md-1">
            <button class="btn btn-default btn-sm" type="button" data-action='editAction'>{{translate 'Edit'}}</button>
        </div>
    {{/unless}}

    <div class="col-md-10">
        {{translate actionType scope='Workflow' category='actionTypes'}}{{#if displayedLinkedEntityName}} <span class="text-muted">&raquo;</span> {{{displayedLinkedEntityName}}}{{/if}}

        <div class="field-list small" style="margin-top: 12px;">
            {{#if actionData.fieldList}}
                {{#each actionData.fieldList}}
                    <div class="field-row cell form-group" data-field="{{./this}}">
                        <label class="control-label">{{translate ./this category='fields' scope=../linkedEntityName}}</label>
                        <div class="field-container field" data-field="{{./this}}"></div>
                    </div>
                {{/each}}
            {{/if}}
        </div>

        <div class="field hidden" data-name="formula">{{{formula}}}</div>
    </div>
</div>