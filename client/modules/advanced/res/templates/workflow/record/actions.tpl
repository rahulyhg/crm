<div>
    <div class="actions margin"></div>
    {{#unless readOnly}}
    <div class="btn-group">
        <button class="btn btn-default" type="button" data-toggle="dropdown"><span class="glyphicon glyphicon-plus"></span></button>
        <ul class="dropdown-menu">
        {{#each actionTypeList}}
            <li><a href="javascript:" data-action="addAction" data-type="{{this}}">{{translate this scope="Workflow" category="actionTypes"}}</a></li>
        {{/each}}
        </ul>
    </div>
    {{/unless}}
</div>

