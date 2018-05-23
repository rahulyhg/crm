<div>
    <div class="customMergeField"></div>
        <div class="btn-group margin">
            <button class="btn btn-default" type="button" data-action="addMergeField"><span class="glyphicon glyphicon-plus"></span></button>
            <ul class="dropdown-menu">
            {{#each fieldList}}
                <li><a href="javascript:" data-action="removeMergeField" data-field="{{this}}">{{translate this scope=../entityType category="fields"}}</a></li>
            {{/each}}
            </ul>
        </div>
</div>

