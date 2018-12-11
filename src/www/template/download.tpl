<article id="download" class="panel">
    <header>
        <h2>Download</h2>
    </header>

    <form action="#download" method="get">
        <div>
            <div class="row">
                <div class="col-6 col-12-medium">
                    <input type="text" name="id" placeholder="ID"
                        value="{$smarty.get.id}" id="job_id" />
                </div>
                <div class="col-6 col-12-medium">
                    <input type="submit" value="Retrieve" />
                </div>
            </div>
        </div>
    </form>

    <div class="results_here"></div>


</article>