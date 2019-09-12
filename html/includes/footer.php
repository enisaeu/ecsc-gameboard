        <!-- BEGIN: prompt box -->
        <div class="modal" id="prompt-box" tabindex="-1" role="dialog" autocomplete="off">
            <div class="modal-dialog" role="document" style="width: 350px">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"></h5>
                        <button class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary">Send</button>
                        <button class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- END: prompt box -->

        <!-- BEGIN: message box -->
        <div class="modal" id="message-box" tabindex="-1" role="dialog" autocomplete="off">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"></h5>
                        <button class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div style="margin: 10px">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- END: message box -->

        <script>
<?php
$value = "";
for ($i = 0; $i < strlen($_SESSION["token"]); $i++) {
    if ($value)
        $value .= ",";
    $value .= ord($_SESSION["token"][$i]);
}
echo "              document.token = String.fromCharCode(" . $value . ");\n";
?>
        </script>

        <noscript>
            Javascript is disabled in your browser. You must have Javascript enabled to utilize the functionality of this page!
        </noscript>
    </body>
</html>
