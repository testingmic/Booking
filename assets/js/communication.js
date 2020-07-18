(function($) {
    $.fn.smsArea = function(options) {

        var e = this,
            cutStrLength = 0,

            s = $.extend({

                cut: true,
                maxSmsNum: 3,
                interval: 0,

                counters: {
                    message: $('#smsCount'),
                    character: $('#smsLength')
                },

                lengths: {
                    ascii: [145, 306, 459],
                    unicode: [70, 134, 201]
                }
            }, options);


        e.keyup(function() {

            var smsType,
                smsLength = 0,
                smsCount = -1,
                charsLeft = 0,
                text = e.val(),
                isUnicode = false;

            for (var charPos = 0; charPos < text.length; charPos++) {
                switch (text[charPos]) {
                    case "\n":
                    case "[":
                    case "]":
                    case "\\":
                    case "^":
                    case "{":
                    case "}":
                    case "|":
                    case "€":
                        smsLength += 2;
                        break;

                    default:
                        smsLength += 1;
                }

                //!isUnicode && text.charCodeAt(charPos) > 127 && text[charPos] != "€" && (isUnicode = true)
                if (text.charCodeAt(charPos) > 127 && text[charPos] != "€")
                    isUnicode = true;
            }

            if (isUnicode) smsType = s.lengths.unicode;
            else smsType = s.lengths.ascii;

            for (var sCount = 0; sCount < s.maxSmsNum; sCount++) {

                cutStrLength = smsType[sCount];
                if (smsLength <= smsType[sCount]) {

                    smsCount = sCount + 1;
                    charsLeft = smsType[sCount] - smsLength;
                    break
                }
            }

            if (s.cut) e.val(text.substring(0, cutStrLength));
            smsCount == -1 && (smsCount = s.maxSmsNum, charsLeft = 0);

            if (smsLength > 0) {
                $(".cancel-message").removeClass("d-none");
                $(".type-message").removeClass("d-none");
            } else {
                $(".cancel-message").addClass("d-none");
                $(".type-message").addClass("d-none");
            }

            s.counters.message.html(smsCount);
            s.counters.character.html(charsLeft);
            calculateSMSUnitCost();

        }).keyup()
    }
}(jQuery));


//Start
$(function() {
    $('#smsText').smsArea({ maxSmsNum: 3 });
})

$(".select-rec-category").on("change", function() {

    var recipient = $(this).val();
    var msg_type = $(this).data("message-type");

    $.ajax({
        url: `${baseUrl}api/sms/category?msg_type=${msg_type}&recipient=${recipient}`,
        type: "GET",
        dataType: "json",
        beforeSend: function() {
            $(".show-recipient-cat:visible").html(`
                <p class="text-center">
                    <span class="fa fa-spin fa-spinner"></span>
                </p>
            `);
        },
        success: function(response) {

            $(".show-recipient-cat:visible").html(response.data.result);

            if (msg_type == 'sms') {
                calculateSMSUnitCost();
                getMultiSelectValues();
            }

        },
        error: function() {
            $(".show-recipient-cat:visible").html(`
                <p class="text-center alert alert-danger">
                    Error Occurred.
                </p>
            `);
            setTimeout(function() {
                $(".show-recipient-cat:visible").fadeOut(1200);
            }, 3000);
        },
        complete: function() {
            if ($(`[name="recipient-lists"]`).length) {
                $(`[name="recipient-lists"]`).select2();
            }
        }
    });

});


var loadMessageHistory = (contactId) => {

    let presetId = $(`span[class="current-viewer"]`).attr('data-contact-id');

    var messagesHistory;

    if (presetId.length > 5) {
        messagesHistory = $(`a[data-history-id='${presetId}']`).attr('data-history');
    } else {
        messagesHistory = $(`a[data-history-id='${contactId}']`).attr('data-history');
    }

    let jsonMessages = $.parseJSON(messagesHistory);

    var msgList = ``,
        status = ``;

    $.each(jsonMessages, function(i, e) {
        if (e.sms_status == 'Sent') {
            status = `<span class="badge badge-success">Sent</span>`;
        } else if (e.sms_status == 'Failed') {
            status = `<span class="badge badge-danger">Failed</span>`;
        } else if (e.sms_status == 'Pending') {
            status = `<span class="badge badge-primary">Pending</span>`;
        }
        msgList += `
			<p style="margin-bottom: 5px">
	         	${e.message}
	         	<br>
	         	<strong>Date: </strong>${e.date_sent} | 
	         	<strong>Status: </strong>${status}
	         </p><br>
		`;
    });

    $(`div[class~="sms-message-sent"]`).html(msgList);
}

var loadBulkMessageHistory = (historyId) => {

    $(`div[class~="sms-message-sent"]`).html(``);

    let contactDetails = $(`a[data-bulk-history-id='${historyId}']`).attr('data-recipients-info');
    let messageContent = $(`a[data-bulk-history-id='${historyId}']`).attr('data-message');

    let jsonContacts = $.parseJSON(contactDetails);

    var msgList = ``,
        status = ``,
        sentList = ``;

    $.each(jsonContacts, function(i, e) {
        if (e.message_status == 'Sent') {
            status = `<span class="badge badge-success">Sent</span>`;
        } else if (e.message_status == 'Failed') {
            status = `<span class="badge badge-danger">Failed</span>`;
        } else if (e.message_status == 'Pending') {
            status = `<span class="badge badge-primary">Pending</span>`;
        }

        sentList += `${e.fullname} ${status} | `;
    });

    msgList = `
        <p>${sentList}</p><br><hr>
        <p style="margin-bottom: 5px">
            ${messageContent}
         </p><br>
    `;

    $(`div[class~="sms-message-sent"]`).html(msgList);
}

var checkSMSBalance = () => {
    if ($("#get-sms-balance").length) {
        $.ajax({
            url: `${baseUrl}api/sms/check-balance`,
            type: "GET",
            dataType: "json",
            beforeSend: function() {
                $("#get-sms-balance").html(`Loading balance &nbsp;<span class="fa fa-spin fa-spinner"></span>`);
            },
            success: function(response) {
                if (response.code == 200) {
                    $("#get-sms-balance").html(response.data.result.show_balance).attr("data-sms-balance", response.data.result.balance);
                } else {
                    $("#get-sms-balance").html('0 SMS Units');
                }
            },
            error: function() {
                $("#get-sms-balance").html(`Balance Error!`);
            }
        });
    }
}
checkSMSBalance();

var fetchHistoryOfSMS = () => {

    $.each(['.bulk-history-lists', '.single-history-lists'], function(key, value) {

        if ($(value).length) {
            var group = $(value).data("group");
            let display = $(value);
            $.ajax({
                url: `${baseUrl}api/sms/history?group=${group}`,
                type: "GET",
                dataType: "json",
                beforeSend: function() {
                    display.html(`<p class="text-center"><span class="fa fa-spin fa-spinner"></span><br>Loading...</p>`);
                },
                success: function(response) {
                    if (response.code == 200) {
                        display.empty();
                        $.each(response.data.result, function(i, e) {

                            display.append(response.data.result[i].list);

                            $(`a[data-history-id='${response.data.result[i].recipients}']`, display).on("click", function() {
                                $(`span[class="current-viewer"]`).attr('data-contact-id', response.data.result[i].recipients);
                                $(".recipient-icon, .get-sms-detail").removeClass("d-none");
                                $(".chat-recipient-title").html(response.data.result[i].recipientName);
                                $(`[name="selectedrecipients"]`).val(response.data.result[i].recipients);
                                $(`[name="messageDirection"]`).val("process_2");
                                $(`.read-message`).removeClass("d-none");
                                loadMessageHistory(i);
                            });

                            $(`a[data-bulk-history-id='${i}']`, display).on("click", function() {
                                $(`div[class~="sms-message-sent"]`).html(``);
                                $(`span[class="current-viewer"]`).attr('data-contact-id', response.data.result[i].recipients);
                                $(".recipient-icon, .get-sms-detail").removeClass("d-none");
                                $(".chat-recipient-title").html(response.data.result[i].recipientName);
                                $(`p[class~="chat-date"]`).html(response.data.result[i].full_date);
                                $(`[name="selectedrecipients"]`).val(response.data.result[i].recipients);
                                $(`[name="messageDirection"]`).val("process_2");
                                $(`.read-message`).removeClass("d-none");
                                loadBulkMessageHistory(i);
                            });
                        });
                    } else {
                        display.html(`<p class='text-center'><em>No records found</em></p>`);
                    }
                },
                error: function() {
                    display.html(`
                        <p class="text-center">
                            Error Processing Request
                        </p>
                    `);
                },
                complete: function() {}
            });

        }
    });
}
fetchHistoryOfSMS();

var calculateSMSUnitCost = () => {

    if ($("#showSMSCost").length) {

        var totalCost = 0;
        var unit = $("#smsCount").html();
        var totalContact = 0;

        if ($(`[name="messageDirection"]`).val() == "process_1") {
            if ($(".append-lists").length) {

                totalContact = $(".append-lists").data("total-contacts");

            } else if ($(`[name="recipient-lists"]`).length) {

                totalContact = $(`[name="recipient-lists"]`).val();

                totalContact = totalContact.length;

            }
        } else {
            if ($(`[name="selectedrecipients"]`).val().length) {
                totalContact = $(`[name="selectedrecipients"]`).val().split(",").length;
            }
        }

        totalCost = (totalContact > 0 && unit > 0) ? (totalContact * unit) : 0;

        if (totalCost > $("[data-sms-balance]").data("sms-balance")) {
            $(".top-up-sms-activate").removeClass("d-none");
            $(".send-message:visible").attr("disabled", "disabled");
        } else {
            $(".top-up-sms-activate").addClass("d-none");
            $(".send-message:visible").prop("disabled", false);
        }

        $("#showSMSCost").html(totalCost + " Unit(s)");

    }
}
calculateSMSUnitCost();

var getMultiSelectValues = () => {

    $(`select[name="recipient-lists"] > option`).on('click', function(e) {
        calculateSMSUnitCost();
    });

}

var topUpSMSUnit = () => {

    $(".top-up-sms").on("click", function(e) {
        e.preventDefault();

        $.ajax({
            url: `${baseUrl}api/sms/topup-form`,
            type: "GET",
            dataType: "json",
            beforeSend: function() {
                $(`.launchModal`).modal("show");
                $(".show-modal-title").html(`<span class="mdi mdi-coins"></span> SMS Top Up`);
                $(".show-modal-body").html(`
                    <p class="text-center mt-2 mb-2"><span class="fa fa-spinner fa-spin"></span></p>
                `);
            },
            success: function(response) {
                $(".show-modal-body").html(response.data.result);
            },
            error: function() {
                $(".show-modal-body").html(`
                    <p class="text-center alert alert-danger mt-2 mb-2">
                        Error Processing Request.
                    </p>
                `);
            }

        });

    });

}
topUpSMSUnit();

var marketingSMSHistory = () => {

    $.each(['.marketing-bulk-history-lists', '.marketing-single-history-lists'], function(key, value) {

        if ($(value).length) {

            var group = $(value).data("group");
            let display = $(value);
            $.ajax({
                url: `${baseUrl}api_v1/marketing/history?group=${group}`,
                type: "GET",
                dataType: "json",
                beforeSend: function() {
                    display.html(`<p class="text-center"><span class="fa fa-spin fa-spinner"></span><br>Loading...</p>
                    `);
                },
                success: function(response) {
                    if (response.code == 200) {
                        display.empty();

                        $.each(response.data.result, function(i, e) {

                            display.append(response.data.result[i].list);

                            $(`a[data-history-id='${response.data.result[i].recipients}']`, display).on("click", function() {
                                $(`span[class="current-viewer"]`).attr('data-contact-id', response.data.result[i].recipients);
                                $(".recipient-icon, .get-sms-detail").removeClass("d-none");
                                $(".chat-recipient-title").html(response.data.result[i].recipientName);
                                $(`[name="selectedrecipients"]`).val(response.data.result[i].recipients);
                                $(`[name="messageDirection"]`).val("process_2");
                                $(`.read-message`).removeClass("d-none");
                                loadMessageHistory(i);
                            });

                            $(`a[data-bulk-history-id='${i}']`, display).on("click", function() {
                                $(`div[class~="sms-message-sent"]`).html(``);
                                $(`span[class="current-viewer"]`).attr('data-contact-id', response.data.result[i].recipients);
                                $(".recipient-icon, .get-sms-detail").removeClass("d-none");
                                $(".chat-recipient-title").html(response.data.result[i].recipientName);
                                $(`p[class~="chat-date"]`).html(response.data.result[i].full_date);
                                $(`[name="selectedrecipients"]`).val(response.data.result[i].recipients);
                                $(`[name="messageDirection"]`).val("process_2");
                                $(`.read-message`).removeClass("d-none");
                                loadBulkMessageHistory(i);
                            });
                        });
                    } else {
                        display.html(`<p class='text-center'><em>No records found</em></p>`);
                    }
                },
                error: function() {
                    display.html(`
                        <p class="text-center">
                            Error Processing Request
                        </p>
                    `);
                },
                complete: function() {}
            });

        }

    });

}
marketingSMSHistory();