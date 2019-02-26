<article id="upload" class="panel">
    <header>
        <h2>Upload</h2>
    </header>

    <p>Only MGF files are supported.</p>

    <form enctype="multipart/form-data" action="?page=upload"
        method="POST">
        <div>
            <div class="row">
                <div class="col-6 col-12-medium">
                    <input type="text" name="name" placeholder="Name" />
                </div>
                <div class="col-6 col-12-medium">
                    <input type="text" name="email" placeholder="Email" />
                </div>
                <div class="col-12">
                    <input type="file" name="rawFile" />
                </div>
                <div class="col-12">
                    <select name="fasta"> {foreach
                        from=$fastaFiles item=$fastaFile}
                        <option>{$fastaFile}</option> {/foreach}
                    </select>
                </div>
                <div class="col-6 col-12-medium">
                    <select name="enzyme">
                        <option value="1" selected="selected">Trypsin</option>
                    </select>
                </div>
                <div class="col-6 col-12-medium">
                    <select name="missed_cleavages">
                        <option value="0">No missed cleavage</option>
                        <option value="1" selected="selected">1
                            missed cleavage</option>
                        <option value="2">2 missed cleavage</option>
                        <option value="3">3 missed cleavage</option>
                    </select>
                </div>
                <div class="col-6 col-12-medium">
                    <input type="number" step=".1" min="0" max="20"
                        name="precursorTolerance"
                        placeholder="Precursor Tolerance (ppm)" />
                </div>
                <div class="col-6 col-12-medium">
                    <input type="number" step=".1" min="0" max="20"
                        name="fragmentTolerance"
                        placeholder="Fragment Tolerance (ppm)" />
                </div>
                <div class="col-6 col-12-medium">Fixed
                    Modifications</div>
                <div class="col-6 col-12-medium">Variable
                    Modifications</div>
                <div class="col-6 col-12-medium">
                    <select name="fixed[]" multiple="multiple">
                        <option value="carb">Carba... (C)</option>
                    </select>
                </div>
                <div class="col-6 col-12-medium">
                    <select name="variable[]" multiple="multiple">
                        <option value="7">Deamidation (NQ)</option>
                        <option value="35">Oxidation (M)</option>
                        <option value="21">Phospho (STY)</option>
                    </select>
                </div>
                <div class="col-12">
                    <input type="submit" value="Search" />
                </div>
            </div>
        </div>
    </form>
</article>