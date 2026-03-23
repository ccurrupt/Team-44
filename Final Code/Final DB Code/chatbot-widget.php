<?php
/**
 * chatbot-widget.php — EveryWear Floating Chat Widget
 *
 * Include on any page using:  <?php include 'chatbot-widget.php'; ?>
 *
 * Sends user messages to chatbot.php backend via fetch().
 * Displays bot replies with typing animation and unread badge.
 * Includes quick-reply suggestion buttons on welcome.
 * Supports dark mode.
 *
 * @version 2.0
 */
?>
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet">

<style>
/* ── CHAT WIDGET CONTAINER ── */
.chat-widget {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 9999;
    font-family: 'Poppins', 'Inter', sans-serif;
}

/* ── FLOATING TOGGLE BUTTON ── */
.chat-toggle {
    height: 60px;
    width: 60px;
    border-radius: 50%;
    border: none;
    cursor: pointer;
    font-size: 26px;
    color: #000;
    background: linear-gradient(to right, #30CDF5, #00FAA0);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    position: relative;
}
.chat-toggle:hover {
    transform: scale(1.1);
    box-shadow: 0 8px 25px rgba(0,0,0,0.25);
}
.chat-toggle i {
    font-size: 28px;
    color: #000;
}

/* ── UNREAD BADGE ── */
.chat-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #ff3b3b;
    color: white;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 20px;
    display: none;
    min-width: 18px;
    text-align: center;
}

/* ── CHAT WINDOW ── */
.chat-box {
    width: 360px;
    height: 480px;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.18);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: absolute;
    bottom: 75px;
    right: 0;
    opacity: 0;
    transform: translateY(20px) scale(0.95);
    pointer-events: none;
    transition: opacity 0.25s ease, transform 0.25s ease;
}
.chat-box.active {
    opacity: 1;
    transform: translateY(0) scale(1);
    pointer-events: auto;
}

/* ── HEADER ── */
.chat-header {
    background: linear-gradient(to right, #30CDF5, #00FAA0);
    padding: 14px 18px;
    font-weight: 600;
    font-size: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #000;
    flex-shrink: 0;
}
.chat-header-info {
    display: flex;
    align-items: center;
    gap: 8px;
}
.chat-header-info i {
    font-size: 20px;
}
.chat-header-status {
    font-size: 11px;
    font-weight: 400;
    opacity: 0.7;
}
.chat-header button {
    background: transparent;
    border: none;
    cursor: pointer;
    font-size: 20px;
    color: #000;
    padding: 4px;
    border-radius: 6px;
    transition: background 0.2s;
}
.chat-header button:hover {
    background: rgba(0,0,0,0.1);
}

/* ── MESSAGES AREA ── */
.chat-messages {
    flex: 1;
    padding: 16px;
    overflow-y: auto;
    background: #f7f8fa;
    scroll-behavior: smooth;
}

/* ── MESSAGE BUBBLES ── */
.chat-msg {
    max-width: 80%;
    padding: 10px 14px;
    margin-bottom: 10px;
    border-radius: 16px;
    font-size: 13.5px;
    line-height: 1.5;
    display: flex;
    align-items: flex-start;
    gap: 8px;
    animation: chatMsgIn 0.25s ease;
    word-wrap: break-word;
    white-space: pre-wrap;
}
@keyframes chatMsgIn {
    from { opacity: 0; transform: translateY(8px) scale(0.95); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
.chat-msg i {
    font-size: 16px;
    margin-top: 2px;
    flex-shrink: 0;
}
.chat-msg.bot {
    background: #e7e9eb;
    color: #1f2937;
    border-bottom-left-radius: 4px;
}
.chat-msg.user {
    background: linear-gradient(to right, #30CDF5, #00FAA0);
    color: #000;
    margin-left: auto;
    border-bottom-right-radius: 4px;
}

/* ── QUICK REPLIES ── */
.chat-quick-replies {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 8px;
    margin-bottom: 6px;
}
.chat-quick-btn {
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 20px;
    padding: 6px 14px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-family: inherit;
    color: #374151;
}
.chat-quick-btn:hover {
    background: linear-gradient(to right, #30CDF5, #00FAA0);
    border-color: transparent;
    color: #000;
    transform: translateY(-1px);
}

/* ── TYPING INDICATOR ── */
.chat-typing {
    display: flex;
    gap: 4px;
    padding: 10px 14px;
    background: #e7e9eb;
    border-radius: 14px;
    width: fit-content;
    margin-bottom: 10px;
}
.chat-typing span {
    width: 6px;
    height: 6px;
    background: #666;
    border-radius: 50%;
    animation: chatBounce 1.2s infinite;
}
.chat-typing span:nth-child(2) { animation-delay: 0.2s; }
.chat-typing span:nth-child(3) { animation-delay: 0.4s; }
@keyframes chatBounce {
    0%, 80%, 100% { transform: scale(0.6); opacity: 0.5; }
    40%           { transform: scale(1);   opacity: 1;   }
}

/* ── INPUT AREA ── */
.chat-input-area {
    display: flex;
    align-items: center;
    border-top: 1px solid #eee;
    padding: 10px 12px;
    background: #f9f9f9;
    flex-shrink: 0;
    gap: 8px;
}
.chat-input-area input {
    flex: 1;
    border: none;
    padding: 12px 16px;
    font-size: 14px;
    min-height: 44px;
    border-radius: 22px;
    outline: none;
    background: white;
    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
    font-family: inherit;
}
.chat-input-area input:focus {
    box-shadow: 0 2px 10px rgba(48,205,245,0.2);
}
.chat-input-area button {
    border: none;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    cursor: pointer;
    color: #000;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(to right, #30CDF5, #00FAA0);
    transition: transform 0.2s ease;
    flex-shrink: 0;
}
.chat-input-area button:hover {
    transform: scale(1.08);
}

/* ── DARK MODE SUPPORT ── */
body.dark .chat-box { background: #1f2937; }
body.dark .chat-messages { background: #111827; }
body.dark .chat-msg.bot { background: #374151; color: #e5e7eb; }
body.dark .chat-input-area { background: #1f2937; border-color: #374151; }
body.dark .chat-input-area input { background: #374151; color: #f9fafb; }
body.dark .chat-header { color: #000; }
body.dark .chat-quick-btn { background: #374151; color: #e5e7eb; border-color: #4b5563; }
body.dark .chat-quick-btn:hover { background: linear-gradient(to right, #30CDF5, #00FAA0); color: #000; }

/* ── RESPONSIVE ── */
@media (max-width: 420px) {
    .chat-box {
        width: calc(100vw - 32px);
        height: calc(100vh - 120px);
        bottom: 70px;
        right: -8px;
        border-radius: 14px;
    }
}
</style>

<!-- ── CHAT WIDGET HTML ── -->
<div class="chat-widget">

    <button id="ewChatToggle" class="chat-toggle" aria-label="Open support chat">
        <i class="ri-chat-3-fill"></i>
        <span id="ewChatBadge" class="chat-badge">0</span>
    </button>

    <div id="ewChatBox" class="chat-box" role="dialog" aria-label="Support chat">

        <div class="chat-header">
            <div class="chat-header-info">
                <i class="ri-customer-service-2-fill"></i>
                <div>
                    <div>EveryWear Support</div>
                    <div class="chat-header-status">Online — typically replies instantly</div>
                </div>
            </div>
            <button id="ewChatClose" aria-label="Close chat">
                <i class="ri-close-line"></i>
            </button>
        </div>

        <div id="ewChatMessages" class="chat-messages"></div>

        <div class="chat-input-area">
            <input id="ewChatInput" type="text" placeholder="Type a message..." autocomplete="off" />
            <button id="ewChatSend" aria-label="Send message">
                <i class="ri-send-plane-fill"></i>
            </button>
        </div>

    </div>
</div>

<script>
(() => {
    /* ── DOM refs ── */
    const toggle   = document.getElementById("ewChatToggle");
    const box      = document.getElementById("ewChatBox");
    const closeBtn = document.getElementById("ewChatClose");
    const sendBtn  = document.getElementById("ewChatSend");
    const input    = document.getElementById("ewChatInput");
    const msgArea  = document.getElementById("ewChatMessages");
    const badge    = document.getElementById("ewChatBadge");

    let unread = 0;
    let welcomed = false;

    /* ── Open / Close ── */
    toggle.onclick = () => {
        const opening = !box.classList.contains("active");
        box.classList.toggle("active");

        if (opening) {
            unread = 0;
            badge.style.display = "none";
            input.focus();

            // Show welcome message on first open
            if (!welcomed) {
                welcomed = true;
                setTimeout(() => {
                    addMessage("Hi 👋 Welcome to EveryWear support! How can I help you?", "bot");
                    showQuickReplies([
                        "Browse products",
                        "Delivery info",
                        "Returns policy",
                        "Student discount",
                        "Track my order",
                        "Help"
                    ]);
                }, 400);
            }
        }
    };

    closeBtn.onclick = () => box.classList.remove("active");

    /* ── Add message bubble ── */
    function addMessage(text, type) {
        const msg = document.createElement("div");
        msg.className = "chat-msg " + type;

        const icon = document.createElement("i");
        icon.className = type === "user"
            ? "ri-user-3-fill"
            : "ri-customer-service-2-fill";

        const span = document.createElement("span");
        span.textContent = text;

        msg.append(icon, span);
        msgArea.appendChild(msg);
        msgArea.scrollTop = msgArea.scrollHeight;

        // Unread badge when chat is closed
        if (!box.classList.contains("active") && type === "bot") {
            unread++;
            badge.textContent = unread;
            badge.style.display = "block";
        }
    }

    /* ── Quick-reply buttons ── */
    function showQuickReplies(options) {
        const wrapper = document.createElement("div");
        wrapper.className = "chat-quick-replies";

        options.forEach(text => {
            const btn = document.createElement("button");
            btn.className = "chat-quick-btn";
            btn.textContent = text;
            btn.onclick = () => {
                wrapper.remove();
                input.value = text;
                sendMessage();
            };
            wrapper.appendChild(btn);
        });

        msgArea.appendChild(wrapper);
        msgArea.scrollTop = msgArea.scrollHeight;
    }

    /* ── Typing indicator ── */
    function showTyping() {
        const t = document.createElement("div");
        t.className = "chat-typing";
        t.id = "ewTyping";
        t.innerHTML = "<span></span><span></span><span></span>";
        msgArea.appendChild(t);
        msgArea.scrollTop = msgArea.scrollHeight;
    }

    function removeTyping() {
        const t = document.getElementById("ewTyping");
        if (t) t.remove();
    }

    /* ── Send message to backend ── */
    function sendMessage() {
        const text = input.value.trim();
        if (!text) return;

        addMessage(text, "user");
        input.value = "";
        showTyping();

        // Remove any leftover quick replies
        const oldQuick = msgArea.querySelectorAll(".chat-quick-replies");
        oldQuick.forEach(el => el.remove());

        fetch("chatbot.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "message=" + encodeURIComponent(text)
        })
        .then(res => {
            if (!res.ok) throw new Error("Server error: " + res.status);
            return res.json();
        })
        .then(data => {
            removeTyping();
            addMessage(data.reply ?? "Sorry, I couldn't understand that.", "bot");
        })
        .catch(err => {
            removeTyping();
            console.error("Chatbot error:", err);
            addMessage("Something went wrong. Please try again.", "bot");
        });
    }

    sendBtn.onclick = sendMessage;

    input.addEventListener("keypress", e => {
        if (e.key === "Enter") sendMessage();
    });

    /* ── Welcome badge on page load ── */
    window.addEventListener("load", () => {
        setTimeout(() => {
            if (!box.classList.contains("active")) {
                unread = 1;
                badge.textContent = "1";
                badge.style.display = "block";
            }
        }, 2000);
    });
})();
</script>
