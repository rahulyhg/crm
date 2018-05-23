<div class="row report-control-panel">
    <div class="report-runtime-filters-contanier col-md-12">{{{runtimeFilters}}}</div>
    <div class="col-md-4 col-md-offset-8">
        <div class="button-container clearfix">
            <div class="btn-group pull-right">
                <button class="btn btn-primary{{#unless hasRuntimeFilters}} hidden{{/unless}}" data-action="run">&nbsp;&nbsp;{{translate 'Run' scope='Report'}}&nbsp;&nbsp;</button>
                <button class="btn btn-default{{#if hasRuntimeFilters}} hidden{{/if}}" data-action="refresh" title="{{translate 'Refresh'}}">&nbsp;&nbsp;<span class="glyphicon glyphicon-refresh"></span>&nbsp;&nbsp;</button>
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">
                    <li><a href="javascript:" data-action="export">{{translate 'Export'}}</a></li>
                    {{#if hasSendEmail}}
                    <li><a href="javascript:" data-action="sendInEmail">{{translate 'Send Email' scope='Report'}}</a></li>
                    {{/if}}
                </ul>
            </div>
        </div>
    </div>
</div>
<div class="report-results-container"></div>
