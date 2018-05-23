<div class="row">
    {{#unless readOnly}}
        <div class="col-md-1">
            <button class="btn btn-default btn-sm" type="button" data-action='editAction'>{{translate 'Edit'}}</button>
        </div>
    {{/unless}}

    <div class="col-md-10">
        {{translate actionType scope='Workflow' category='actionTypes'}}

        <div class="field-list small" style="margin-top: 12px;">
            <div class="field-row cell form-group execution-time-container" data-field="execution-time">
                <div class="field" data-field="execution-time">{{{executionTime}}}</div>
            </div>

            {{#if actionData.workflowId}}
                <div class="field-row cell form-group" data-field="workflow">
                    <label class="control-label">{{translate 'Workflow' scope='Workflow' category='labels'}}</label>
                    <div class="field-container field field-workflow" data-field="workflow">{{{workflow}}}</div>
                </div>
            {{/if}}
        </div>
    </div>
</div>