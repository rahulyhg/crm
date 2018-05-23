<div class="row">
    {{#unless readOnly}}
        <div class="col-md-1">
            <button class="btn btn-default btn-sm" type="button" data-action='editAction'>{{translate 'Edit'}}</button>
        </div>
    {{/unless}}

    <div class="col-md-10">
        {{translate actionType scope='Workflow' category='actionTypes'}}

        <div class="field-list small" style="margin-top: 12px;">

            <div class="field-row cell form-group" data-field="methodName">
                <label class="control-label">{{translate 'methodName' scope='Workflow' category='labels'}}</label>
                <div class="field-container field field-methodName" data-field="methodName">{{{methodName}}}</div>
            </div>

            {{#if actionData.additionalParameters}}
                <div class="field-row cell form-group" data-field="additionalParameters">
                    <label class="control-label">{{translate 'additionalParameters' category='labels' scope='Workflow'}}</label>
                    <div class="field-container field field-additionalParameters" data-field="additionalParameters">{{{additionalParameters}}}</div>
                </div>
            {{/if}}

        </div>
    </div>
</div>

