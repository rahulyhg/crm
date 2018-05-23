<div>
    <h5>{{translate 'All' scope='Workflow'}} <small>({{translate 'allMustBeMet' category='texts' scope='Workflow'}})</small></h5>
    <div class="all-conditions">
        <div class="no-data form-group small" style="margin-left: 20px;">{{translate 'No Data'}}</div>
    </div>
    {{#unless readOnly}}
        <div class="btn-group">
            <button class="btn btn-default" type="button" data-toggle="dropdown"><span class="glyphicon glyphicon-plus"></span></button>
            <ul class="dropdown-menu">
            {{#each fieldList}}
                <li><a href="javascript:" data-action="addCondition" data-type="all" data-field="{{this}}">{{translate this scope=../entityType category="fields"}}</a></li>
            {{/each}}
            </ul>
        </div>
    {{/unless}}
</div>
<div style="margin-top: 30px;">
    <h5>{{translate 'Any' scope='Workflow'}} <small>({{translate 'atLeastOneMustBeMet' category='texts' scope='Workflow'}})</small></h5>
    <div class="any-conditions">
        <div class="no-data form-group small" style="margin-left: 20px;">{{translate 'No Data'}}</div>
    </div>
    {{#unless readOnly}}
        <div class="btn-group">
            <button class="btn btn-default" type="button" data-toggle="dropdown"><span class="glyphicon glyphicon-plus"></span></button>
            <ul class="dropdown-menu">
            {{#each fieldList}}
                <li><a href="javascript:" data-action="addCondition" data-type="any" data-field="{{this}}">{{translate this scope=../entityType category="fields"}}</a></li>
            {{/each}}
            </ul>
        </div>
    {{/unless}}
</div>
{{#if hasFormula}}
<div style="margin-top: 30px;">
    <h5>{{translate 'Formula' scope='Workflow'}} <small>({{translate 'formulaInfo' category='texts' scope='Workflow'}})</small></h5>
    <div class="formula-conditions">
    </div>
</div>
{{/if}}
