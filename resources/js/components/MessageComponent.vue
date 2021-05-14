<template>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Messages</div>

                    <div class="card-body">
                        <div class="mh-300px overflow-auto" id="chat-container">
                            <div class="clearfix m" v-for="message in messages">
                                <span v-if="message.from_user !== null">{{ message.from_user.name }}: {{ message.message }}</span>
                                <span v-else>BookTripExample: {{ message.message }}</span>
                            </div>
                        </div>

                        <div class="input-group mt-3">
                            <input type="text" name="message" class="form-control" placeholder="Type your message here..." v-model="newMessage" @keyup.enter="sendMessage" >

                            <button class="btn btn-primary ml-3" @click="sendMessage">Send</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        data: function() {
            return {
                user: null,
                messages: [],
                newMessage: ''
            }
        },
        created() {
            this.getCurrentUser()
            this.getMessage()

            Echo.private('chat')
                .listen('MessageSentEvent', (e) => {
                    console.log('MessageSentEvent', e)
                    console.log('user', this.user)
                    if (e.fromUser == this.user || e.toUser == this.user) {
                        console.log('push there', this.messages)
                        this.messages.push({
                            message: e.message.message,
                            from_user: e.message.from_user,
                            to_user: e.message.to_user
                        })
                        console.log('push after', this.messages)
                    }
                })
        },
        watch: {
            messages: function (val, oldVal) {
               this.$nextTick(function () {
                   this.scrollToBottom()
               });
            }
        },
        methods: {
            scrollToBottom() {
                var container = this.$el.querySelector("#chat-container");
                container.scrollTop = container.scrollHeight;
            },

            getCurrentUser() {
                axios.get('/user').then(response => {
                    this.user = response.data.data.id
                })
            },

            getMessage() {
                axios.get('/messages').then(response => {
                    this.messages = response.data.data
                })
            },

            addMessage(message) {
                var params = {
                    'message': message
                }

                axios.post('/messages', params).then(response => {
                    if (response.data.data.status == 'failed') {alert(response.data.message)}
                })
            },

            sendMessage() {
                this.addMessage(this.newMessage)
                this.newMessage = ''
            }
        }
    }
</script>
