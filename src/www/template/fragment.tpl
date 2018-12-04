<h1>{$precursor.title}</h1>

<div style="float: right; position: absolute; top: 0px; right: 0px;">
    <dl style="float: right;">
        <dt>Charge</dt>
        <dd>{$precursor.charge}</dd>
        <dt>Mass/Charge</dt>
        <dd>{$precursor.mass_charge|round:4}</dd>
        <dt>Retention Time (sec)</dt>
        <dd>{$precursor.rtinseconds|round:2}</dd>
        <dt>Tolerance</dt>
        <dd>{$tolerance->getTolerance()}{$tolerance->getUnit()}</dd>
    </dl>
    <dl style="float: right; margin-right: 2em;">
        <dt>ID</dt>
        <dd>{$precursor.id}</dd>
        <dt>Mass</dt>
        <dd>{$precursor.mass|round:4}</dd>
        <dt>Scans</dt>
        <dd>{$precursor.scans}</dd>
        <dt>Peptide</dt>
        <dd>{$peptide->getSequence()}</dd>
    </dl>
    <br /> {foreach $plots as $plotPath} {if $plotPath != false} <img
        src="?page=fragment&amp;plot={$plotPath}" /><br /> {/if}
    {/foreach}
</div>

<div style="float: right;"></div>

<table>
    <tr>
        <td>
            <h2>Fragments</h2>
        </td>
        <td>
            <h2>B Ions</h2>
        </td>
        <td>
            <h2>Y Ions</h2>
        </td>
    </tr>
    <tr>
        <td rowspan="{($modShifts|Count * 2)+2}" valign="top">
            <table>
                <thead>
                    <tr style="background-color: #ababab;">
                        <th style="width: 100px;">#</th>
                        <th style="width: 100px;">Mass/Charge</th>
                        <th style="width: 100px;">Intensity</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $fragments as $key => $fragment}
                    <tr style="background-color: {cycle values="#efefef,#fcfcfc"};">
                        <td style="text-align: right;">{$key}</td>
                        <td style="text-align: right;">{$fragment.mz|number_format:3}</td>
                        <td style="text-align: right;">{$fragment.intensity|number_format:3}</td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </td>
        <td colspan="2"><h3>Unmodified</h3></td>
    </tr>
    <tr>
        <td style="vertical-align: top;">
            <table>
                <thead>
                    <tr style="background-color: #ababab;">
                        <th style="width: 100px;">AA</th>
                        <th style="width: 100px;">m/z</th>
                        <th style="width: 100px;">Closest Match</th>
                        <th style="width: 100px;">Da</th>
                        <th style="width: 100px;">ppm</th>

                    </tr>
                </thead>
                <tbody>
                    {foreach $bIons as $key=> $ion} {$key = $key -1}
                    <tr style="background-color: {cycle values="#efefef,#fcfcfc"};{if $ion['isMatch']}font-weight:bold;color:red;{/if}">
                        <td style="text-align: right;">{$peptideArray.$key}</td>
                        <td style="text-align: right;">{$ion.ion|number_format:3}</td>
                        <td style="text-align: right;">#{$ion.match}</td>
                        <td style="text-align: right;">{$ion.da|number_format:3}</td>
                        <td style="text-align: right;">{$ion.ppm|number_format:2}</td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </td>
        <td style="vertical-align: top;">
            <table>
                <thead>
                    <tr style="background-color: #ababab;">
                        <th style="width: 100px;">AA</th>
                        <th style="width: 100px;">m/z</th>
                        <th style="width: 100px;">Closest Match</th>
                        <th style="width: 100px;">Da</th>
                        <th style="width: 100px;">ppm</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $yIons as $key => $ion}
                        {$key = ($yIons|count)-$key}
                    <tr style="background-color: {cycle values="#efefef,#fcfcfc"};{if $ion['isMatch']}font-weight:bold;color:red;{/if}">
                        <td style="text-align: right;">{$peptideArray.$key}</td>
                        <td style="text-align: right;">{$ion.ion|number_format:3}</td>
                        <td style="text-align: right;">#{$ion.match}</td>
                        <td style="text-align: right;">{$ion.da|number_format:3}</td>
                        <td style="text-align: right;">{$ion.ppm|number_format:2}</td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </td>
    </tr>
            {foreach $modShifts as $modName => $modShift}
            {$modIndex = $modShift@index}
    <tr>
        <td colspan="2"><h3>{$modName}</h3></td>
    </tr>
    <tr>
        <td style="vertical-align: top;">        
            <table>
                <thead>
                    <tr style="background-color: #ababab;">
                        <th style="width: 100px;">AA</th>
                        <th style="width: 100px;">m/z</th>
                        <th style="width: 100px;">Closest Match</th>
                        <th style="width: 100px;">Da</th>
                        <th style="width: 100px;">ppm</th>

                    </tr>
                </thead>
                <tbody>
                    {foreach $bIons as $key=> $ion}
                        {$ion = $bIons[$key]['mod'][$modIndex]}
                        {$key = $key -1}
                    <tr style="background-color: {cycle values="#efefef,#fcfcfc"};{if $ion['isMatch']}font-weight:bold;color:red;{/if}">
                        <td style="text-align: right;">{$peptideArray.$key}</td>
                        <td style="text-align: right;">{$ion.ion|number_format:3}</td>
                        <td style="text-align: right;">#{$ion.match}</td>
                        <td style="text-align: right;">{$ion.da|number_format:3}</td>
                        <td style="text-align: right;">{$ion.ppm|number_format:2}</td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </td>
        <td style="vertical-align: top;">
            <table>
                <thead>
                    <tr style="background-color: #ababab;">
                        <th style="width: 100px;">AA</th>
                        <th style="width: 100px;">m/z</th>
                        <th style="width: 100px;">Closest Match</th>
                        <th style="width: 100px;">Da</th>
                        <th style="width: 100px;">ppm</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $yIons as $key => $ion} 
                        {$ion = $yIons[$key]['mod'][$modIndex]}
                        {$key = ($yIons|count)-$key}
                    <tr style="background-color: {cycle values="#efefef,#fcfcfc"};{if $ion['isMatch']}font-weight:bold;color:red;{/if}">
                        <td style="text-align: right;">{$peptideArray.$key}</td>
                        <td style="text-align: right;">{$ion.ion|number_format:3}</td>
                        <td style="text-align: right;">#{$ion.match}</td>
                        <td style="text-align: right;">{$ion.da|number_format:3}</td>
                        <td style="text-align: right;">{$ion.ppm|number_format:2}</td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </td>
    </tr>
            
            
            {/foreach}
</table>