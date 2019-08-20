var COUNTRIES = { AF: "Afghanistan", AX: "Aland Islands", AL: "Albania", DZ: "Algeria", AS: "American Samoa", AD: "Andorra", AO: "Angola", AI: "Anguilla", AG: "Antigua and Barbuda", AR: "Argentina", AM: "Armenia", AW: "Aruba", AU: "Australia", AT: "Austria", AZ: "Azerbaijan", BS: "Bahamas", BH: "Bahrain", BD: "Bangladesh", BB: "Barbados", BY: "Belarus", BE: "Belgium", BZ: "Belize", BJ: "Benin", BM: "Bermuda", BT: "Bhutan", BO: "Bolivia", BQ: "Bonaire", BA: "Bosnia and Herzegovina", BW: "Botswana", BR: "Brazil", BN: "Brunei Darussalam", BG: "Bulgaria", BF: "Burkina Faso", BI: "Burundi", CV: "Cabo Verde", KH: "Cambodia", CM: "Cameroon", CA: "Canada", KY: "Cayman Islands", CF: "Central African Republic", TD: "Chad", CL: "Chile", CN: "China", CX: "Christmas Island", CC: "Cocos (Keeling) Islands", CO: "Colombia", KM: "Comoros", CK: "Cook Islands", CR: "Costa Rica", HR: "Croatia", CU: "Cuba", CW: "Curaçao", CY: "Cyprus", CZ: "Czech Republic", CI: "Cote d'Ivoire", CD: "Congo", DK: "Denmark", DJ: "Djibouti", DM: "Dominica", DO: "Dominican Republic", EC: "Ecuador", EG: "Egypt", SV: "El Salvador", GQ: "Equatorial Guinea", ER: "Eritrea", EE: "Estonia", ET: "Ethiopia", FK: "Falkland Islands", FO: "Faroe Islands", FM: "Micronesia", FJ: "Fiji", FI: "Finland", MK: "FYR Macedonia", FR: "France", GF: "French Guiana", PF: "French Polynesia", GA: "Gabon", GM: "Gambia", GE: "Georgia", DE: "Germany", GH: "Ghana", GI: "Gibraltar", GR: "Greece", GL: "Greenland", GD: "Grenada", GP: "Guadeloupe", GU: "Guam", GT: "Guatemala", GG: "Guernsey", GN: "Guinea", GW: "Guinea-Bissau", GY: "Guyana", HT: "Haiti", VA: "Holy See", HN: "Honduras", HK: "Hong Kong", HU: "Hungary", IS: "Iceland", IN: "India", ID: "Indonesia", IR: "Iran", IQ: "Iraq", IE: "Ireland", IM: "Isle of Man", IL: "Israel", IT: "Italy", JM: "Jamaica", JP: "Japan", JE: "Jersey", JO: "Jordan", KZ: "Kazakhstan", KE: "Kenya", KI: "Kiribati", KW: "Kuwait", KG: "Kyrgyzstan", LA: "Laos", LV: "Latvia", LB: "Lebanon", LS: "Lesotho", LR: "Liberia", LY: "Libya", LI: "Liechtenstein", LT: "Lithuania", LU: "Luxembourg", MO: "Macau", MG: "Madagascar", MW: "Malawi", MY: "Malaysia", MV: "Maldives", ML: "Mali", MT: "Malta", MH: "Marshall Islands", MQ: "Martinique", MR: "Mauritania", MU: "Mauritius", YT: "Mayotte", MX: "Mexico", MD: "Moldova", MC: "Monaco", MN: "Mongolia", ME: "Montenegro", MS: "Montserrat", MA: "Morocco", MZ: "Mozambique", MM: "Myanmar", NA: "Namibia", NR: "Nauru", NP: "Nepal", NL: "Netherlands", NC: "New Caledonia", NZ: "New Zealand", NI: "Nicaragua", NE: "Niger", NG: "Nigeria", NU: "Niue", NF: "Norfolk Island", KP: "North Korea", MP: "Northern Mariana Islands", NO: "Norway", OM: "Oman", PK: "Pakistan", PW: "Palau", PA: "Panama", PG: "Papua New Guinea", PY: "Paraguay", PE: "Peru", PH: "Philippines", PN: "Pitcairn", PL: "Poland", PT: "Portugal", PR: "Puerto Rico", QA: "Qatar", CG: "Republic of the Congo", RO: "Romania", RU: "Russia", RW: "Rwanda", RE: "Réunion", BL: "Saint Barthélemy", SH: "Saint Helena", KN: "Saint Kitts and Nevis", LC: "Saint Lucia", MF: "Saint Martin", WS: "Samoa", SM: "San Marino", ST: "Sao Tome and Principe", SA: "Saudi Arabia", SN: "Senegal", RS: "Serbia", SC: "Seychelles", SL: "Sierra Leone", SG: "Singapore", SX: "Sint Maarten", SK: "Slovakia", SI: "Slovenia", SB: "Solomon Islands", SO: "Somalia", ZA: "South Africa", KR: "South Korea", SS: "South Sudan", ES: "Spain", LK: "Sri Lanka", PS: "State of Palestine", SD: "Sudan", SR: "Suriname", SJ: "Svalbard and Jan Mayen", SZ: "Swaziland", SE: "Sweden", CH: "Switzerland", SY: "Syrian Arab Republic", TW: "Taiwan", TJ: "Tajikistan", TZ: "Tanzania", TH: "Thailand", TL: "Timor-Leste", TG: "Togo", TK: "Tokelau", TO: "Tonga", TT: "Trinidad and Tobago", TN: "Tunisia", TR: "Turkey", TM: "Turkmenistan", TC: "Turks and Caicos Islands", TV: "Tuvalu", UG: "Uganda", UA: "Ukraine", AE: "United Arab Emirates", GB: "United Kingdom", US: "United States of America", UY: "Uruguay", UZ: "Uzbekistan", VU: "Vanuatu", VE: "Venezuela", VN: "Vietnam", VG: "Virgin Islands (British)", VI: "Virgin Islands (U.S.)", WF: "Wallis and Futuna", EH: "Western Sahara", YE: "Yemen", ZM: "Zambia", ZW: "Zimbabwe" };
var LOG_LEVELS = { debug: 0, info: 1, warning: 2, error: 3, critical: 4 }
var FAST_PULL_PERIOD = 2000;
var SLOW_PULL_PERIOD = 5000;
var LAST_UPDATE_SLOW_THRESHOLD = 60000; // after ms of chat inactivity turn on SLOW_PULL_PERIOD

var alerts = {};
var lastPullUpdate = new Date();

// Reference: https://stackoverflow.com/a/37544400
if (!String.prototype.endsWith) {
    String.prototype.endsWith = function(searchString, position) {
        var subjectString = this.toString();
        if (typeof position !== 'number' || !isFinite(position) || Math.floor(position) !== position || position > subjectString.length) {
        position = subjectString.length;
        }
        position -= searchString.length;
        var lastIndex = subjectString.indexOf(searchString, position);
        return lastIndex !== -1 && lastIndex === position;
    };
}

$(document).ready(function() {
    $(document).ajaxError(function(event, jqXHR, options, status) {
        if (status === "Unauthorized")
            reload();
        else
            console.error("Something went wrong ('" + jqXHR.status + " " + status + "')");
    });

    // Reference: https://stackoverflow.com/a/18169689
    $(document).on("hidden.bs.modal", function (event) {
        $(event.target).data("bs.modal", null);
        $(event.target).remove();
    });

    $("input[name='token']").attr("value", document.token);

    if ($("h1").length === 0)
        return;

    $(window).resize(function() {
        repositionSidebar();
    });

    repositionSidebar();

    $(".card").not("#contract_editor").find(".collapse").each(function() {
        if (localStorage.getItem($(this).prop("id")) == "hide") {
            $(this).removeClass("show");
            $(this).closest(".card").find("[data-toggle=collapse]").addClass("collapsed");
        }
    });

    $(".collapse").on("show.bs.collapse", function(event) {
        localStorage.setItem($(event.target).prop("id"), "show");
    }).on("hide.bs.collapse", function(event) {
        localStorage.setItem($(event.target).prop("id"), "hide");
    });

    $(".flag-icon").each(function() {
        if ($(this).attr("title"))
            $(this).attr("title", COUNTRIES[$(this).attr("title")]);
    });

    pathdir = $("img[id=logo]").attr("src").split("resources")[0];

    if (pathdir.endsWith('/'))
        pathdir = pathdir.substring(0, pathdir.length-1);

//     Reference: https://xdsoft.net/jqplugins/datetimepicker/
    var dateTimePickerLogic = function(currentDateTime) {
        if ((currentDateTime !== null) && (currentDateTime.getDay() == 6)) {
            this.setOptions({
                minTime: "8:00"
            });
        } else
            this.setOptions({
                minTime: "6:00"
            });
    };

    $("#datetime_start, #datetime_end").datetimepicker({
        mask: true,
        onChangeDateTime: dateTimePickerLogic,
        minDate: 0,
//         format: 'd-m H:i'
    });

    if ($("#line_momentum").length)
        $.post(window.location.href.split('#')[0], {token: document.token, action: "momentum"}, function(content) {
            if (!content)
                return;

            try {
                result = JSON.parse(content);
            }
            catch(e) {
                console.error(e);
                return;
            }

            var MAX_TOP_TEAMS = 10;
            var datasets = [];
            var minTime = Number.MAX_SAFE_INTEGER || 9007199254740991;  // Reference: https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Number/MAX_SAFE_INTEGER
            var maxTime = 0;
            var maxCash = 0;
            var totalPoints = 0;

            for (var team_name in result)
                totalPoints += result[team_name]["cash"].length;

            var lineThickness = Math.max(1, 3 - Math.floor(totalPoints / 1000));

            for (var team_name in result) {
                dataPoints = [];
                for (var i = 0; i < result[team_name]["cash"].length; i++) {
                    current = result[team_name]["cash"][i];
                    dataPoints.push({ x: new Date(current["x"] * 1000), y: current["y"] });
                    minTime = Math.min(minTime, current["x"]);
                    maxTime = Math.max(maxTime, current["x"]);
                    maxCash = Math.max(maxCash, current["y"]);
                }

                dataset = { 
                    type: "line",
                    lineThickness: lineThickness,
                    color: getHashColor(team_name),
                    axisYType: "secondary",
                    name: team_name,
                    showInLegend: true,
                    markerSize: 2 * lineThickness,
//                     yValueFormatString: "#,###,#k",
                    dataPoints: dataPoints
                };
                datasets.push(dataset);

                if (datasets.length >= MAX_TOP_TEAMS)
                    break;
            }

            if ((result.length == 0) || (isAdmin() && (maxCash == 0))) {
                $("#line_momentum").hide(100, function() { $("#line_momentum").remove(); });
                return;
            }

            if (maxTime === 0)
                maxTime = parseInt(Date.now() / 1000);
            else
                maxTime = ((parseInt(Date.now() / 1000) - maxTime) < 3600 * 6) ? parseInt(Date.now() / 1000) : Math.min(maxTime + (maxTime - minTime) / 4, parseInt(Date.now() / 1000));  // maximum of inactivity to show (competition could be over)

            var timePadding = ((maxTime - minTime) / 15) * 1000;
            var fontFamily = "Arial";
            var timeFormat = "";

            if ((Date.now() / 1000) - minTime < 3600)
                timeFormat = "HH:mm";  // :ss will be appended down below
            else if ((Date.now() / 1000) - minTime < 24 * 3600)
                timeFormat = "HH:mm";
//             else if ((Date.now() / 1000) - minTime < 7 * 24 * 3600)
//                 timeFormat = "DDD HH:mm";
            else
                timeFormat = "DD-MMM HH:mm";

            if (maxTime - minTime < 3600)
                timeFormat += ":ss";

            var chart = new CanvasJS.Chart("line_momentum", {
                title: {
                    text: "Top 10 Teams",
                    fontSize: 14,
                    fontFamily: "Arial"
                },
                axisX: {
                    title: "Time",
                    titleFontFamily: fontFamily,
                    titleFontSize: 12,
                    titleFontStyle: "bold",
                    valueFormatString: timeFormat,
                    labelFontFamily: fontFamily,
                    labelFontSize: 12,
                    minimum: minTime * 1000 - timePadding,
                    maximum: maxTime * 1000 + timePadding,
                },
                axisY2: {
                    title: "Cash (€)",
                    titleFontFamily: fontFamily,
                    titleFontSize: 12,
                    titleFontStyle: "bold",
//                     prefix: "€",
                    suffix: "",
                    labelFontFamily: fontFamily,
                    labelFontSize: 12,
                    gridThickness: 1,
                    maximum: Math.max(maxCash < 500 ? 500 : 1000, maxCash) * 1.15,
                    labelFormatter: function ( e ) {
                        return (maxCash > 1000) ? (e.value / 1000).toLocaleString() + (e.value ? "k" : "") : e.value.toLocaleString();
//                         return (e.value / 1000) + (e.value ? "k" : "");
                    }
                },
                toolTip: {
                    shared: true,
                    fontFamily: fontFamily,
                    borderColor: "#dee2e6",
                    borderThickness: 1,
                    fontStyle: "normal"
                },
                legend: {
                    cursor: "pointer",
                    verticalAlign: "top",
                    horizontalAlign: "center",
                    dockInsidePlotArea: true,
                    itemclick: toogleDataSeries,
                    fontFamily: fontFamily,
//                     fontSize: 12,
                },
                data: datasets
            });
            chart.render();

            $(".canvasjs-chart-credit").css("display", "none");

            function toogleDataSeries(e) {
                if (typeof(e.dataSeries.visible) === "undefined" || e.dataSeries.visible) {
                    e.dataSeries.visible = false;
                } else {
                    e.dataSeries.visible = true;
                }
                chart.render();
            }
    
        });

    pullMessages(true);

    if (!isAdmin()) {
        $(".counter").each(function() {
            if ($(this).text().search(/^[1-9]/) === -1) {
                $(this).closest("a").addClass("not-active");
    //             $(this).closest(".nav-link").addClass("disabled");
            }
        });
    }

    // dirty patch for a bug when skipping goto link
    $(".nav-link[href]").closest(".nav-item").click(function() {
        if ($(this).find(".nav-link").not(".not-active").length) {
            document.location = $(this).find(".nav-link").prop("href");
            return false;
        }
    });

    $(".fa-envelope").click(function(event) {
        var row = $(event.target).closest("tr");
        var login_name = row.find("sup").text().substr(1).slice(0, -1);
        var full_name = row.find("td:nth-child(2)").html().replace(/ <sup>.+/, "").replace(/<span.+<\/span>/, "");
        showSendMessageBox(login_name, full_name);
    });

    $(".fa-money-bill-wave").click(function(event) {
        var row = $(event.target).closest("tr");
        var login_name = row.find("sup").text().substr(1).slice(0, -1);
        var full_name = row.find("td:nth-child(2)").html().replace(/ <sup>.+/, "").replace(/<span.+<\/span>/, "");
        showSendCashBox(login_name, full_name);
    });

    $(".fa-hand-holding-usd").click(function(event) {
        var row = $(event.target).closest("tr");
        var login_name = row.find("sup").text().substr(1).slice(0, -1);
        var full_name = row.find("td:nth-child(2)").html().replace(/ <sup>.+/, "").replace(/<span.+<\/span>/, "");
        showAwardCashBox(login_name, full_name);
    });

    $("#settings_table input[type=checkbox]").click(function() {
        var name = $(this).prop("id");
        var value = $(this).is(":checked");
        pushSetting(name, value);
    });

    $("#settings_table input[type=text]").change(function() {
        var name = $(this).prop("id");
        var value = $(this).val();
        pushSetting(name, value);
    });

    $("#settings_table input[type=number]").change(function() {
        var name = $(this).prop("id");
        var value = $(this).val();
        pushSetting(name, value);
    });

    $("#chat_room").change(function() {
        $("#chat_messages").find("div").remove();
            pullMessages();
    });

    $(".actions i").css("cursor", "pointer");
    $(".actions i").click(function() {
        $(this).attr("data-original-title");
        $(this).closest("tr").find(".full_name").text();
        
    });

    $("[ts]").each(function() {
        $(this).text(formatDateTime($(this).attr("ts")));
    });

    $(".full_name").each(function() {
        var full_name = $(this).html().replace(/ <sup>.+/, "");
        var color = getHashColor(full_name);
//         $(this).find("sup").css("color", getHashColor(full_name));
        $(this).prepend($("<span class='mr-1' style='color: " + color + "'>&#9646;</span>"));
    });


    // Reference: https://stackoverflow.com/a/3160718
    $("table").each(function() {
        $(this).find("th")
            .wrapInner("<span title=\"Sort by this column\"/>")
            .each(function() {
                var th = $(this),
                    thIndex = th.index(),
                    inverse = false;

                th.click(function() {
                    $(this).closest("table").find("td").filter(function() {
                        return $(this).index() === thIndex;
                    }).sortElements(function(a, b) {
                        if ($(a).find(".flag-icon").length)
                            return $(a).find("[title]").attr("data-original-title") > $(b).find("[title]").attr("data-original-title") ?
                                inverse ? -1 : 1
                                : inverse ? 1 : -1;
                        else if ($(a).is("[value]") && $(b).is("[value]"))
                            return parseFloat($(a).attr("value")) > parseFloat($(b).attr("value")) ?
                                inverse ? -1 : 1
                                : inverse ? 1 : -1;
                        else if ($.isNumeric($(a).text().replace(",", "")) && $.isNumeric($(b).text().replace(",", "")))
                            return parseFloat($.text([a]).replace(",", "")) > parseFloat($.text([b]).replace(",", "")) ?
                                inverse ? -1 : 1
                                : inverse ? 1 : -1;
                        else if ($(a).find(".log-level").length)
                            return LOG_LEVELS[$(a).text()] > LOG_LEVELS[$(b).text()] ?
                                inverse ? -1 : 1
                                : inverse ? 1 : -1;
                        else
                            return $.text([a]) > $.text([b]) ?
                                inverse ? -1 : 1
                                : inverse ? 1 : -1;
                    }, function(){
                        // parentNode is the element we want to move
                        return this.parentNode; 
                    });
                    inverse = !inverse;
                });
                    
            });
    });

    periodicPullMessages();

    $('[data-toggle="tooltip"]').tooltip();

    // Note: just in case
    repositionSidebar();
});

// Reference: https://stackoverflow.com/a/570027
function reload() {
    window.location = window.location.href.split('#')[0];
}

function pushSetting(name, value) {
    $.post(window.location.href.split('#')[0], {token: document.token, action: "update", setting: name, value: value}).fail(function(jqXHR) {
        alert("Something went wrong ('" + jqXHR.responseText + "')!");
    });
}

function hideNotification(notification_id) {
    $.post(window.location.href.split('#')[0], {token: document.token, action: "hide", notification_id: notification_id}, function() {
        var count = $(".notification").length;
        $("#notification_count").text(count);

        if (count === 0)
            $("#notification_count").closest("a").addClass("not-active");
        else
            $("#notification_count").closest("a").removeClass("not-active");
    }).fail(function(jqXHR) {
        alert("Something went wrong ('" + jqXHR.responseText + "')!");
    });
}

function deleteNotification(notification_id) {
    $.post(window.location.href.split('#')[0], {token: document.token, action: "delete", notification_id: notification_id}, function() {
        var count = $(".notification").length;
        $("#notification_count").text(count);
    }).fail(function(jqXHR) {
        alert("Something went wrong ('" + jqXHR.responseText + "')!");
    });
}

var shownMessageBox = false;
function showMessageBox(title, message, style) {
    style = style || "primary";  // Reference: https://stackoverflow.com/a/15178735

    $(document).ready(function() {
        var dialog = $("#message-box").clone();

        if (dialog.length === 0)
            return;

        else if (shownMessageBox && (message.includes(" no ")))
            return;

        else if ($(".modal.fade.show .modal-body:contains(" + message.replace(/\(.+/, "") + ")").length > 0) {
            return;
        }

        dialog.removeAttr("id");
        dialog.find(".modal-title").text(title);
        dialog.find(".modal-body div").text(message);
        dialog.find(".btn").addClass("btn-" + style);

        // Reference: https://stackoverflow.com/a/31909778
        dialog.on('shown.bs.modal', function () {
            $(this).find('.btn').focus();
        });

       shownMessageBox = true;
       dialog.modal();
    });
}

function showResetBox(login_name, full_name) {
    var dialog = $("#prompt-box").clone();

    if (dialog.length === 0)
        return;

    dialog.removeAttr("id");
    dialog.find(".modal-title").text("Reset data");
    dialog.find(".modal-body").html("");

    dialog.find(".modal-body").append($('<div class="custom-control custom-checkbox"><input class="custom-control-input" id="delete_teams" type="checkbox"><label class="custom-control-label" for="delete_teams">Delete teams</label></div>'));
    dialog.find(".modal-body").append($('<div class="custom-control custom-checkbox"><input class="custom-control-input" id="delete_contracts" type="checkbox"><label class="custom-control-label" for="delete_contracts">Delete contracts</label></div>'));
    dialog.find(".modal-body").append($('<div class="custom-control custom-checkbox"><input class="custom-control-input" id="delete_chat" type="checkbox" checked><label class="custom-control-label" for="delete_chat">Delete chat messages</label></div>'));
    dialog.find(".modal-body").append($('<div class="custom-control custom-checkbox"><input class="custom-control-input" id="delete_privates" type="checkbox" checked><label class="custom-control-label" for="delete_privates">Delete privates (i.e. private messages and cash sends)</label></div>'));
    dialog.find(".modal-body").append($('<div class="custom-control custom-checkbox"><input class="custom-control-input" id="delete_auxiliary" type="checkbox" checked><label class="custom-control-label" for="delete_auxiliary">Delete auxiliary (i.e. accepted contracts, solved tasks and notifications)</label></div>'));
    //localStorage.clear()

    // Reference: https://stackoverflow.com/a/31909778
    dialog.on('shown.bs.modal', function () {
        $(this).find('.btn-secondary').focus();
    });

    dialog.find(".btn-primary").text("Reset").addClass("btn-danger").removeClass("btn-primary");

//     dialog.dialog("option", "width", 460);

    dialog.find(".btn-danger").off("click");
    dialog.find(".btn-danger").click(function() {
        $.post(window.location.href.split('#')[0], {token: document.token, action: "reset", teams: dialog.find("[id=delete_teams]").prop("checked"), contracts: dialog.find("[id=delete_contracts]").prop("checked"), chat: dialog.find("[id=delete_chat]").prop("checked"), privates: dialog.find("[id=delete_privates]").prop("checked"), auxiliary: dialog.find("[id=delete_auxiliary]").prop("checked")}, function(content) {
            if (content === "OK")
                reload();
            else
                alert("Something went wrong ('" + content + "')!");
        });
    });

    dialog.modal();
}

function showDatabaseBox(login_name, full_name) {
    var dialog = $("#prompt-box").clone();

    if (dialog.length === 0)
        return;

    dialog.removeAttr("id");
    dialog.find(".modal-title").text("Database");
    dialog.find(".modal-body").html("");

    dialog.find(".modal-body").append($('<button type="button" class="btn btn-info">Export</button>'));
    dialog.find(".modal-body").append($('<button type="button" class="btn btn-warning ml-2">Import</button>'));
    dialog.find(".modal-body").append($('<form method="post" enctype="multipart/form-data" class="hidden"><input type="file" name="import_file" id="import_file"><input type="hidden" name="action" value="import"><input type="hidden" name="token" value="' + document.token + '"><input type="submit" name="submit"></form>'));

    // Reference: https://stackoverflow.com/a/31909778
    dialog.on('shown.bs.modal', function () {
        $(this).find('.btn-secondary').focus();
    });

    dialog.find(".btn-primary").hide();

//     dialog.dialog("option", "width", 460);

    dialog.find("button:contains('Export')").off("click");
    dialog.find("button:contains('Export')").click(function() {
        $.post(window.location.href.split('#')[0], {token: document.token, action: "export"}, function(content) {
            if (content.indexOf("INSERT INTO") > -1) {
                var blob = new Blob([content], { type: "application/octet-stream" });
                var a = document.createElement("a");
                a.href = window.URL.createObjectURL(blob);
                a.download = "ecsc.sql";
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }
            else
                alert("Something went wrong ('" + content + "')!");
        });
    });

    dialog.find("button:contains('Import')").click(function() {
        dialog.find("#import_file").trigger("click");
        dialog.find("input[type=file]").change(function() {
            dialog.find("input[type=submit]").trigger("click");
        });
    });

    dialog.modal();
}

function showChangePasswordBox(login_name, full_name) {
    var dialog = $("#prompt-box").clone();

    if (dialog.length === 0)
        return;

    dialog.removeAttr("id");
    dialog.find(".modal-title").text("Change password");
    dialog.find(".modal-body").html("");
    dialog.find(".modal-body").append($("<input name='password' type='password' value='' placeholder='(change here)' autocomplete='new-password' class='form-control mb-2' style='width: 100%' required>"));
    dialog.find(".modal-body").append($("<input name='password2' type='password' value='' placeholder='(re-enter here)' autocomplete='new-password' class='form-control' style='width: 100%' required>"));

    // Reference: https://stackoverflow.com/a/31909778
    dialog.on('shown.bs.modal', function () {
        $(this).find('input').first().focus();
    });

    // Reference: https://stackoverflow.com/a/39976531
    // (Note: dirty patch for annoying autocomplete)
    dialog.find("input").on("focus", function() {
        $(this).attr("autocomplete", "on");
    }).blur(function() {    
        $(this).removeAttr("autocomplete");
    });

    var _ = function() {
        var password = dialog.find("[name=password]").val();
        var reenter = dialog.find("[name=password2]").val();

        if (password) {
            if (reenter != password)
                dialog.find("[name=password2]").addClass("is-invalid");
            else
                dialog.find("[type=password]").removeClass("is-invalid");
        }
        else
            dialog.find("[type=password]").addClass("is-invalid");
    };

    dialog.find("input").keypress(function(e) {
        if(e.which == 13) {
            dialog.find(".btn-primary").click();
        }
    });

    dialog.find("[type=password]").change(_).keyup(_).focusout(_).each(_);

    dialog.find(".btn-primary").off("click");
    dialog.find(".btn-primary").click(function(event) {
        var password = dialog.find("[name=password]").val();
        var invalid = dialog.find(".is-invalid").first();

        if (invalid.length == 0) {
            $.post(window.location.href.split('#')[0], {token: document.token, action: "update", password: password}, function(content) {
                if (content === "OK") {
                    dialog.find("[data-dismiss]").click();
                    showMessageBox("Success", "Password successfully changed", "success");
                }
            }).fail(function(jqXHR) {
                alert("Something went wrong ('" + jqXHR.responseText + "')!");
            });
        }
        else
            $(invalid).focus();
    });

    dialog.find(".btn-primary").text("Change");
    dialog.modal();
}

function showYesNoWarningBox(message, onyes) {
    var dialog = $("#prompt-box").clone();

    if (dialog.length === 0)
        return;

    dialog.removeAttr("id");
    dialog.find(".modal-title").text("Warning");
    dialog.find(".modal-body").html("");
    dialog.find(".modal-body").append(message);
    dialog.find(".btn-primary").text("Yes").addClass("btn-warning").removeClass("btn-primary");
    dialog.find(".btn-secondary").text("No").addClass("btn-primary").removeClass("btn-secondary");

    dialog.find('.btn-warning').click(function() {
        if (typeof onyes !== "undefined")
            onyes();
        dialog.find("[data-dismiss]").click();
    });

    dialog.on('shown.bs.modal', function () {
        $(this).find('.btn-primary').focus();
    });

    dialog.modal();
}

function showSendNotificationBox() {
    var dialog = $("#prompt-box").clone();

    if (dialog.length === 0)
        return;

    dialog.removeAttr("id");
    dialog.find(".modal-title").text("Send notification");
    dialog.find(".modal-body").html("");
    dialog.find(".modal-body").append($("<textarea class='form-control' rows=4 placeholder='Notification to everybody' style='width: 100%'></textarea>"));

    // Reference: https://stackoverflow.com/a/31909778
    dialog.on('shown.bs.modal', function () {
        $(this).find('textarea').focus();
    });

    dialog.find(".btn-primary").off("click");
    dialog.find(".btn-primary").click(function(event) {
        var textarea = $(dialog).find("textarea");
        if (textarea.val())
            $.post(window.location.href.split('#')[0], {token: document.token, action: "notification", message: textarea.val()}, function(content) {
                if (content === "OK")
                    reload();
                else
                    alert("Something went wrong ('" + content + "')!");
            });
        else
            wrongValueEffect(textarea);
    });

    dialog.modal();
}

function showSendMessageBox(login_name, full_name) {
    var dialog = $("#prompt-box").clone();

    if (dialog.length === 0)
        return;

    dialog.removeAttr("id");
    dialog.find(".modal-title").text("Send private message");
    dialog.find(".modal-body").html("");
    dialog.find(".modal-body").append($("<textarea class='form-control' rows=4 placeholder='Message to \"" + full_name + "\"' style='width: 100%'></textarea>"));

    // Reference: https://stackoverflow.com/a/31909778
    dialog.on('shown.bs.modal', function () {
        $(this).find('textarea').focus();
    });

    dialog.find(".btn-primary").off("click");
    dialog.find(".btn-primary").click(function(event) {
        var textarea = $(dialog).find("textarea");
        if (textarea.val())
            $.post(window.location.href.split('#')[0], {token: document.token, action: "private", to: login_name, message: textarea.val()}, function() {
                dialog.find("[data-dismiss]").click();
            }).fail(function(jqXHR) {
                alert("Something went wrong ('" + jqXHR.responseText + "')!");
            });
        else
            wrongValueEffect(textarea);
    });

    dialog.modal();
}

function showAwardCashBox(login_name, full_name) {
    var dialog = $("#prompt-box").clone();
    var max = parseInt(($(".current").find("td:nth-child(4)").text().replace(',', '')) || 0);

    if (dialog.length === 0)
        return;

    dialog.removeAttr("id");
    dialog.find(".modal-title").text("Award cash");
    dialog.find(".modal-body").html("");
    dialog.find(".modal-body").append($("<label>Amount:</label><input type='number' name='quantity' class='form-control ml-2' style='width: initial; display: inline-block' value='0' style='width: 6em'><hr>"));
    dialog.find(".modal-body").append($("<textarea class='form-control' rows=4 placeholder='Note (mandatory)' style='width: 100%'></textarea>"));

    // Reference: https://stackoverflow.com/a/31909778
    dialog.on('shown.bs.modal', function () {
        $(this).find('input').focus();
    });

    dialog.find(".btn-primary").off("click");
    dialog.find(".btn-primary").click(function(event) {
        var input = $(dialog).find("input");
        var message = $(dialog).find("textarea").val();
        if ((parseInt(input.val()) != 0) && (message.length > 0))
            $.post(window.location.href.split('#')[0], {token: document.token, action: "private", to: login_name, message: message, cash: $(dialog).find("input").val()}, function(content) {
                if (content === "OK")
                    reload();
            }).fail(function(jqXHR) {
                alert("Something went wrong ('" + jqXHR.responseText + "')!");
            });
        else
            wrongValueEffect(input);
    });

    dialog.modal();
}

function showSendCashBox(login_name, full_name) {
    var dialog = $("#prompt-box").clone();
    var max = parseInt(($(".current").find("td:nth-child(4)").text().replace(',', '')) || 0);

    if (dialog.length === 0)
        return;

    dialog.removeAttr("id");
    dialog.find(".modal-title").text("Send cash");
    dialog.find(".modal-body").html("");
    dialog.find(".modal-body").append($("<label>Amount:</label><input type='number' name='quantity' class='form-control ml-2' style='width: initial; display: inline-block' min='0' max='" + max + "' value='0' style='width: 6em'><hr>"));
    dialog.find(".modal-body").append($("<textarea class='form-control' rows=4 placeholder='Message to \"" + full_name + "\" (optional)' style='width: 100%'></textarea>"));

    // Reference: https://stackoverflow.com/a/31909778
    dialog.on('shown.bs.modal', function () {
        $(this).find('input').focus();
    });

    dialog.find(".btn-primary").off("click");
    dialog.find(".btn-primary").click(function(event) {
        var input = $(dialog).find("input");
        if ((parseInt(input.val()) > 0) && (parseInt(input.val()) <= max))
            $.post(window.location.href.split('#')[0], {token: document.token, action: "private", to: login_name, message: $(dialog).find("textarea").val(), cash: $(dialog).find("input").val()}, function(content) {
                if (content === "OK")
                    reload();
            }).fail(function(jqXHR) {
                alert("Something went wrong ('" + jqXHR.responseText + "')!");
            });
        else
            wrongValueEffect(input);
    });

    dialog.modal();
}

function wrongValueEffect(element) {
    $(element).effect("shake");
    $(element).parent().effect("highlight", {color: "red"});
}

String.prototype.hashCode = function() {
    return murmurhash3_32_gc(this, 13);
};

function pad(n, width, z) {
    z = z || '0';
    n = n + '';

    return n.length >= width ? n : new Array(width - n.length + 1).join(z) + n;
}

function getHashColor(value) {
    return "#" + pad(value.hashCode().toString(16), 6).substring(0, 6);
}

// Reference: https://stackoverflow.com/a/20426113
var special = ['zeroth','first', 'second', 'third', 'fourth', 'fifth', 'sixth', 'seventh', 'eighth', 'ninth', 'tenth', 'eleventh', 'twelfth', 'thirteenth', 'fourteenth', 'fifteenth', 'sixteenth', 'seventeenth', 'eighteenth', 'nineteenth'];
var deca = ['twent', 'thirt', 'fort', 'fift', 'sixt', 'sevent', 'eight', 'ninet'];

// Reference: https://www.w3schools.com/js/js_date_methods.asp
var months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

function stringifyNumber(n) {
    if (n === -1) return "last";
    if (n < 20) return special[n];
    if (n % 10 === 0) return deca[Math.floor(n / 10) - 2] + 'ieth';
    return deca[Math.floor(n / 10)-2] + 'y-' + special[n % 10];
}

// Note: should be same as for #line_momentum (timeFormat)
function formatDateTime(timestamp) {
    var date = new Date(timestamp * 1000);
    var hours = pad(date.getHours(), 2);
    var minutes = pad(date.getMinutes(), 2);
    var seconds = pad(date.getSeconds(), 2);
    var month = months[date.getMonth()];
    var day = pad(date.getDate(), 2);

    return day + '-' + month.substr(0, 3) + ' ' + hours + ':' + minutes.substr(-2) + ':' + seconds.substr(-2);
//     return date.toLocaleDateString() + " " + date.toLocaleTimeString();
}

function formatChatTime(timestamp) {
    return ((Date.now() / 1000) - timestamp < 24 * 3600) ? formatDateTime(timestamp).split(" ")[1] : formatDateTime(timestamp);
}

// Reference: https://raw.githubusercontent.com/garycourt/murmurhash-js/master/murmurhash3_gc.js
function murmurhash3_32_gc(key, seed) {
    var remainder, bytes, h1, h1b, c1, c1b, c2, c2b, k1, i;
    
    remainder = key.length & 3; // key.length % 4
    bytes = key.length - remainder;
    h1 = seed;
    c1 = 0xcc9e2d51;
    c2 = 0x1b873593;
    i = 0;
    
    while (i < bytes) {
        k1 = 
          ((key.charCodeAt(i) & 0xff)) |
          ((key.charCodeAt(++i) & 0xff) << 8) |
          ((key.charCodeAt(++i) & 0xff) << 16) |
          ((key.charCodeAt(++i) & 0xff) << 24);
        ++i;
        
        k1 = ((((k1 & 0xffff) * c1) + ((((k1 >>> 16) * c1) & 0xffff) << 16))) & 0xffffffff;
        k1 = (k1 << 15) | (k1 >>> 17);
        k1 = ((((k1 & 0xffff) * c2) + ((((k1 >>> 16) * c2) & 0xffff) << 16))) & 0xffffffff;

        h1 ^= k1;
        h1 = (h1 << 13) | (h1 >>> 19);
        h1b = ((((h1 & 0xffff) * 5) + ((((h1 >>> 16) * 5) & 0xffff) << 16))) & 0xffffffff;
        h1 = (((h1b & 0xffff) + 0x6b64) + ((((h1b >>> 16) + 0xe654) & 0xffff) << 16));
    }
    
    k1 = 0;
    
    switch (remainder) {
        case 3: k1 ^= (key.charCodeAt(i + 2) & 0xff) << 16;
        case 2: k1 ^= (key.charCodeAt(i + 1) & 0xff) << 8;
        case 1: k1 ^= (key.charCodeAt(i) & 0xff);
        
        k1 = (((k1 & 0xffff) * c1) + ((((k1 >>> 16) * c1) & 0xffff) << 16)) & 0xffffffff;
        k1 = (k1 << 15) | (k1 >>> 17);
        k1 = (((k1 & 0xffff) * c2) + ((((k1 >>> 16) * c2) & 0xffff) << 16)) & 0xffffffff;
        h1 ^= k1;
    }
    
    h1 ^= key.length;

    h1 ^= h1 >>> 16;
    h1 = (((h1 & 0xffff) * 0x85ebca6b) + ((((h1 >>> 16) * 0x85ebca6b) & 0xffff) << 16)) & 0xffffffff;
    h1 ^= h1 >>> 13;
    h1 = ((((h1 & 0xffff) * 0xc2b2ae35) + ((((h1 >>> 16) * 0xc2b2ae35) & 0xffff) << 16))) & 0xffffffff;
    h1 ^= h1 >>> 16;

    return h1 >>> 0;
}

(function($) {
    $.fn.detectFont = function() {
        var fonts = $(this).css('font-family').split(",");
        if ( fonts.length == 1 )
            return fonts[0];

        var element = $(this);
        var detectedFont = null;
        fonts.forEach( function( font ) {
            var clone = element.clone().css({'visibility': 'hidden', 'font-family': font}).appendTo('body');
            if ( element.width() == clone.width() )
                detectedFont = font;
            clone.remove();
        });

       return detectedFont;
    };
})(jQuery);

function pushMessage() {
    var message = $("#chat_message").val();

    if (message) {
        if (!$("#chat_messages").is("[push_lock]")) {
            $("#chat_messages").attr("push_lock", true);

            $.post(window.location.href.split('#')[0], {token: document.token, action: "push", message: message, room: $("#chat_room").val().replace('#', '')}, function(content) {
                if (!content)
                    return;
                else if (content === "OK") {
                    $("#chat_message").val("");
                    pullMessages();
                }
            }).always(function() {
                $("#chat_messages").removeAttr("push_lock");
            });
        }
    }

    return false;
}

function periodicPullMessages() {
    var pull = function() {
        pullMessages();
        setTimeout(pull, ((new Date() - lastPullUpdate < LAST_UPDATE_SLOW_THRESHOLD) || ($(document.activeElement).prop("id") == "chat_message")) ? FAST_PULL_PERIOD : SLOW_PULL_PERIOD);
    };
    setTimeout(pull, FAST_PULL_PERIOD);
}

function pullMessages(initial) {
    initial = initial || false;  // Reference: https://stackoverflow.com/a/15178735

    if (!$("#chat_messages").is("[pull_lock]")) {
        $("#chat_messages").attr("pull_lock", true);

        $.post(window.location.href.split('#')[0], {token: document.token, action: "pull", chat_id: $("#chat_messages div[chat_id]").last().attr("chat_id") || 0, room : $("#chat_room").val().replace('#', '')}, function(content) {
            try {
                result = JSON.parse(content);
            }
            catch(e) {
                console.error(e);
                return;
            }

            var counter = $("#chat_messages").find("div").length;

            for (var i = 0; i < result["chat"].length; i++) {
                message = result["chat"][i];
                if ($("#chat_messages div[chat_id=" + message.id + "]").length !== 0)
                    continue;
                $("#chat_messages").append($("<div chat_id='" + message.id + "'" + (counter % 2 === 0 ? " style='background: #f7f7f7'" : "") + "><p style='padding: 0px; padding-left: 15px; padding-right: 15px; margin: 5px'><b>" + escapeHtml(message.team) + "</b> <span style='font-size: larger'></span><span class='flag-icon flag-icon-" + escapeHtml(message.country).toLowerCase() + "' style='margin: 2px' data-toggle='tooltip' title='" + COUNTRIES[escapeHtml(message.country).toUpperCase()] + "'></span>:<span style='float: right'>(" + formatChatTime(message.ts) + ")</span> <br><span>" + escapeHtml(message.content) + "</span></p></div>"));
                lastPullUpdate = new Date();
                counter += 1;
            }

            if (result["chat"].length)
                $('#chat_messages').animate({scrollTop: $('#chat_messages')[0].scrollHeight}, initial ? 0 : "fast");

            if ($("#notification_count").text() != result["notifications"]) {
                $("#notification_count").text(result["notifications"]);
                if ($(".active").html().indexOf("notification_") >= 0) {
                    setInterval(function() {
                        if (!(($(document.activeElement).prop("id") == "chat_message") && ($(document.activeElement).val())))
                            reload();
                    }, 100);
                }
            }
        }).always(function() {
            $("#chat_messages").removeAttr("pull_lock");
        });
    }
}

// Note: dirty patch for a side-bar alignment
function repositionSidebar() {
    var delta = ($("#navigation_bar").outerHeight() + $("#navigation_bar").offset().top) - $("#side_bar .card").first().offset().top;
    if (delta < 100)
        $("#side_bar").offset({top: $("#side_bar").offset().top + delta});
}

// Reference: https://stackoverflow.com/a/6234804
function escapeHtml(unsafe) {
    return unsafe
         .replace(/&/g, "&amp;")
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;")
         .replace(/"/g, "&quot;")
         .replace(/'/g, "&#039;");
}

function signOut() {
    window.location.search = "signout";
}

function isAdmin() {
    return $("a:contains(Reset)").length > 0;
}

function validator() {
    var invalid = false;
    if ($(this).attr("type") == "number") {
        var value = parseInt($(this).val());
        if (isNaN(value)) {
            $(this)[0].setCustomValidity("Please enter a number.");
            invalid = true;
        }
        else if ($(this).prop("min")) {
            if ((value < parseInt($(this).prop("min")))) {
                $(this)[0].setCustomValidity("Please select a value that is not less than " + $(this).prop("min") + ".");
                invalid = true;
            }
        }
    }
    else if ($(this).val().length === 0) {
        $(this)[0].setCustomValidity("Please fill out this field");
        invalid = true;
    }

    if (invalid)
        $(this).addClass("is-invalid");
    else {
        $(this)[0].setCustomValidity("");
        $(this).removeClass("is-invalid");
    }
}

function countriesDropdown(container) {
    var out = "<option value=''></option>";
    for (var key in COUNTRIES)
        out += "<option value='" + key + "'>" + COUNTRIES[key] + "</option>";
//         out += "<option value='" + key + "'" + (key === "HR" ? " selected='selected'" : "")+ ">" + countries[key] + "</option>";

    $(container).html(out);
}
