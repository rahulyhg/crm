<div class="row">
    {{#unless readOnly}}
        <div class="col-md-1">
            <button class="btn btn-default btn-sm" type="button" data-action='editAction'>{{translate 'Edit'}}</button>
        </div>
    {{/unless}}

    <div class="col-md-10">
        {{translate actionType scope='Workflow' category='actionTypes'}}

        <div class="field-list small" style="margin-top: 12px;">

            <div class="field-row cell form-group" data-field="whatToFollow">
                <label class="control-label">{{translate 'whatToFollow' category='fields' scope='Workflow'}}</label>
                <div class="field-container field field-whatToFollow" data-field="whatToFollow">{{{whatToFollow}}}</div>
            </div>

            <div class="field-row cell form-group" data-field="usersToMakeToFollow">
                <label class="control-label">{{translate 'usersToMakeToFollow' category='fields' scope='Workflow'}}</label>
                <div class="field-container field field-users-to-make-to-follow" data-field="usersToMakeToFollow">{{{usersToMakeToFollow}}}</div>
            </div>

        </div>

    </div>
</div>

