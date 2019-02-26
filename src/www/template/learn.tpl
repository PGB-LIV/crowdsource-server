<article id="about" class="panel">
    <header>
        <h2>About Dracula</h2>
    </header>
    <p>Dracula is an Internet-based search engine for mass
        spectrometry proteomics data that utilises computation from
        volunteer nodes.</p>

    <h3>Powering Dracula</h3>

    <p>The dracula search engine is built using JavaScript
        (ECMAScript 5) which is suitable for execution in common browers
        (i.e. Firefox, Chrome, Internet Explorer, etc.) or in dedicated
        headless engines (i.e. PhantomJS).</p>

    <p>When data is uploaded to Dracula, it is partitioned into
        smaller manageable chunks which are then distributed to clients
        for analysis, and the results are then fed back to be
        re-assembled into a complete result.</p>

    <p>Computation time is received by clients executing the
        JavaScript, either via accessing a web site which hosts the
        JavaScript code, (i.e. this one), or via dedicated cluster nodes
        execution the JavaScript in a headless manner.</p>

    <h3>Joining Dracula</h3>

    <p>There are two ways of joining the Dracula network, the first
        is by hosting the JavaScript client on a web-site that you
        control, Dracula requires jQuery and can be included as follows:</p>

<hr />
    <pre>&lt;script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"&gt;&lt;/script&gt;

&lt;script src="http://pgb.liv.ac.uk:1260/script/DraculaSearch.js">&lt;/script&gt;</pre>
<hr />

    <p>For hosting Dracula on a dedicated cluster node, see the
        GitHub page.</p>

    <h3>Source code</h3>

    <p>Dracula is written in PHP, JavaScript and C#, utilising
        RabbitMQ and MySQL for data storage.</p>

    <p>The source code for all components can be found in their
        respective GitHub repositories:</p>

    <ul>
        <li>PHP data pre-processor/post-processor:</li>
        <li>JavaScript client:</li>
        <li>C# allocation server:</li>
    </ul>

    <h3>Citation</h3>

    <p>Pending.</p>
</article>