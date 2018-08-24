<form enctype="multipart/form-data" action="?page=upload" method="POST">
    <fieldset>
        <label for="mgf">MGF File</label> <input name="mgf" id="mgf"
            type="file" />
    </fieldset>
    <fieldset>
        <label for="fasta">FASTA File</label> <select name="fasta"
            id="fasta"> {foreach from=$fastaFiles
            item=$fastaFile}
            <option>{$fastaFile}</option> {/foreach}
        </select>
    </fieldset>
    <fieldset>
        <label for="precursorTolerance">Precursor Tolerance</label> <select
            name="precursorTolerance" id="precursorTolerance">
            <option>0.1 Da</option>
            <option>0.5 Da</option>
            <option>1.0 Da</option>
            <option selected="selected">5 ppm</option>
            <option>10 ppm</option>
            <option>15 ppm</option>
            <option>20 ppm</option>
        </select>
    </fieldset>
    <fieldset>
        <label for="fragmentTolerance">Fragment Tolerance</label> <select
            name="fragmentTolerance" id="fragmentTolerance">
            <option>0.1 Da</option>
            <option>0.5 Da</option>
            <option>1.0 Da</option>
            <option selected="selected">5 ppm</option>
            <option>10 ppm</option>
            <option>15 ppm</option>
            <option>20 ppm</option>
        </select>
    </fieldset>
    <fieldset>
        <label for="enzyme">Enzyme</label> <select name="enzyme"
            id="enzyme">
            <option value="1" selected="selected">Trypsin</option>
        </select>
    </fieldset>
    <fieldset>
        <label for="fixed">Fixed Carbamidomethyl</label> <input
            name="fixed" id="fixed" type="checkbox" value="Yes"
            checked="checked" /><span>Yes</span>
    </fieldset>
    <fieldset>
        <label for="variable">Variable Modification</label> <select
            name="variable" id="variable">
            <option value="none" selected="selected">None</option>
            <option value="phospho">Phospho (STY)</option>
        </select>
    </fieldset>
    <fieldset>
        <input type="submit" value="Upload" />
    </fieldset>
</form>