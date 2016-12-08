<?php
if (!$adodb->IsConnected())
{
    echo 'ADOdb has failed to connect.';
}
else
{
    echo 'ADOdb is connected.';
}
exit;
