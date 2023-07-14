<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Websokct</title>

</head>
<body>
<script>
    let ws  = new WebSocket("ws://www.bourse.com:18308/test");
    let heartbeatId = null;
    let heartbeatMsg = {
        cmd : "ping",
        data : "heartbeat",
        ext : {test:"123123"},
    };
    ws.onopen = function(){
        heartbeat();
    }
    ws.onmessage = function (evt)
    {
        console.log(evt.data);
    };

    ws.onclose = function()
    {
        console.log("ws close");
        clearTimeout(heartbeatId);
    };

    function heartbeat(){
        ws.send(JSON.stringify(heartbeatMsg));
        heartbeatId = setTimeout(function(){
            heartbeat();
        },30000);
    }
</script>
</body>
</html>
