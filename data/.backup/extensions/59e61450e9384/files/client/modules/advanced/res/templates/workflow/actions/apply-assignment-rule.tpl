<div class="row">
    {{#unless readOnly}}
        <div class="col-md-1">
            <button class="btn btn-default btn-sm" type="button" data-action='editAction'>{{translate 'Edit'}}</button>
        </div>
    {{/unless}}

    <div class="col-md-10">
        {{translate actionType scope='Workflow' category='actionTypes'}}

        <div class="field-list small" style="margin-top: 12px;">
            <div class="cell form-group">
                <label class="control-label">{{translate 'assignmentRule' scope='Workflow' category='fields'}}</label>
                <div class="field" data-name="assignmentRule">
                </div>
            </div>

            <div class="cell form-group">
                <label class="control-label">{{translate 'targetTeam' scope='Workflow' category='fields'}}</label>
                <div class="field" data-name="targetTeam"></div>
            </div>

            <div class="cell form-group">
                <label class="control-label">{{translate 'targetUserPosition' scope='Workflow' category='fields'}}</label>
                <div class="field" data-name="targetUserPosition"></div>
            </div>

            <div class="cell form-group">
                <label class="control-label">{{translate 'listReport' scope='Workflow' category='fields'}}</label>
                <div class="field" data-name="listReport"></div>
            </div>
        </div>
    </div>
</div>