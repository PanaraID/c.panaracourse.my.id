 <?php
 
 use Livewire\Volt\Component;
 use App\Models\Chat;
 use Illuminate\Support\Facades\Auth;
 
 new class extends Component {
     public $chat;
     public bool $showTagsModal = false;
     public $lastTagCount = 0; // Track perubahan tag count
 
     public function mount(Chat $chat)
     {
         $this->chat = $chat;
         $this->lastTagCount = $this->getUnreadTagsCount();
     }
 
     public function openTagsModal()
     {
         $this->showTagsModal = true;
     }
 
     public function closeTagsModal()
     {
         $this->showTagsModal = false;
     }
 
     public function markTagAsRead($tagId)
     {
         $tag = Auth::user()->messageTags()->find($tagId);
         if ($tag) {
             $tag->update(['is_read' => true]);
             // Update count dan dispatch event
             $this->lastTagCount = $this->getUnreadTagsCount();
             $this->dispatch('tag-marked-as-read');
         }
     }
 
     public function refreshTagNotifications()
     {
         $currentCount = $this->getUnreadTagsCount();
 
         // Hanya refresh jika ada perubahan
         if ($currentCount !== $this->lastTagCount) {
             $this->lastTagCount = $currentCount;
             $this->dispatch('$refresh');
 
             // Jika ada tag baru, bisa trigger sound atau notification
             if ($currentCount > $this->lastTagCount) {
                 $this->dispatch('new-tag-alert');
             }
         }
     }
 
     public function getListeners()
     {
         return [
             'new-tag-received' => 'refreshTagNotifications',
             'tag-marked-as-read' => '$refresh',
         ];
     }
 
     private function getUnreadTagsCount()
     {
         return Auth::user()
             ->messageTags()
             ->where('is_read', false)
             ->whereHas('message', function ($query) {
                 $query->where('chat_id', $this->chat->id);
             })
             ->count();
     }
 
     public function getUnreadTagsProperty()
     {
         return Auth::user()
             ->messageTags()
             ->where('is_read', false)
             ->whereHas('message', function ($query) {
                 $query->where('chat_id', $this->chat->id);
             })
             ->with(['message', 'taggedByUser'])
             ->orderByDesc('created_at')
             ->get();
     }
 };
 
 ?>

 <div class="bg-gradient-to-r from-emerald-500 via-green-500 to-teal-500 dark:from-emerald-700 dark:via-green-700 dark:to-teal-700 px-2 py-2 shadow-lg backdrop-blur-sm"
     wire:poll.5s="refreshTagNotifications">
     <div class="flex items-center justify-between">
         <div class="flex items-center space-x-4">
             <!-- Back Button with Animation -->
             <a href="{{ route('chat.index') }}"
                 class="text-white hover:bg-white/20 rounded-full p-2.5 transition-all duration-300 hover:scale-110 hover:rotate-[-5deg] group">
                 <svg class="w-6 h-6 group-hover:translate-x-[-2px] transition-transform" fill="none"
                     stroke="currentColor" viewBox="0 0 24 24">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                         d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                 </svg>
             </a>

             <!-- Chat Info -->
             <div class="flex-1">
                 <h1 class="text-white font-bold text-xl leading-tight drop-shadow-md tracking-tight">
                     {{ $chat->title }}</h1>
                 <p class="text-white/90 text-sm font-medium flex items-center space-x-1">
                     <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                         <path
                             d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                     </svg>
                     <span>{{ $chat->members->count() }} anggota</span>
                 </p>
             </div>
         </div>

         <!-- Header Actions -->
         <div class="flex items-center space-x-1">
            <script>
                function scrollToBottomBre() {
                    const container = document.getElementById('messages-container');
                    if (container) {
                        container.scrollTo({
                            top: container.scrollHeight,
                            behavior: 'smooth'
                        });
                        // Sembunyikan notifikasi pesan baru jika terlihat
                        const element = document.getElementById('new-message-received');
                        if (element) {
                            element.classList.add('hidden');
                        }
                    }
                }
            </script>
             <div id="new-message-received" onclick="scrollToBottomBre()" wire:ignore
                 class="hidden bg-gradient-to-r cursor-pointer from-yellow-400 via-yellow-500 to-yellow-600 dark:from-yellow-600 dark:via-yellow-700 dark:to-yellow-800 text-black dark:text-white px-4 py-2 rounded-xl shadow-lg flex items-center gap-2">
                 <svg class="w-5 h-5 text-black dark:text-white animate-bounce" fill="none" stroke="currentColor"
                     viewBox="0 0 24 24">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                         d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                 </svg>
             </div>

             <!-- Tag Notifications Button -->
             @if ($this->unreadTags->count() > 0)
                 <button wire:click="openTagsModal"
                     class="relative text-white hover:bg-white/20 rounded-full p-2.5 transition-all duration-300 hover:scale-110 group animate-pulse-new-tag">
                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                             d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                     </svg>
                     <span
                         class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold animate-bounce">
                         {{ $this->unreadTags->count() }}
                     </span>
                 </button>
             @endif

             @if (Auth::user()->hasRole('admin') || $chat->created_by === Auth::id())
                 <a href="{{ route('chat.manage', $chat->slug) }}"
                     class="text-white hover:bg-white/20 rounded-full p-2.5 transition-all duration-300 hover:scale-110 hover:rotate-12 group">
                     <svg class="w-5 h-5 group-hover:rotate-90 transition-transform duration-500" fill="none"
                         stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                             d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                         </path>
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                             d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                     </svg>
                 </a>
             @endif
         </div>
     </div>

     <!-- ======================== -->
     <!-- 🏷️ MODAL DAFTAR TAG -->
     <!-- ======================== -->
     @if ($showTagsModal)
         <div class="bg-gradient-to-r from-emerald-500 via-green-500 to-teal-500 dark:from-emerald-700 dark:via-green-700 dark:to-teal-700
                text-white px-6 py-4 border-t border-white/20 backdrop-blur-sm transition-all duration-300"
             wire:click.stop>
             <div class="flex items-center justify-between mb-4">
                 <h3 class="text-lg font-semibold flex items-center gap-2">
                     <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                             d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                     </svg>
                     Tag Anda ({{ $this->unreadTags->count() }})
                 </h3>
                 <button wire:click="closeTagsModal"
                     class="text-white hover:bg-white/20 rounded-full p-1 transition-colors">
                     <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                             d="M6 18L18 6M6 6l12 12" />
                     </svg>
                 </button>
             </div>

             <div class="max-h-96 overflow-y-auto space-y-4 pr-2">
                 @if ($this->unreadTags->count() > 0)
                     @foreach ($this->unreadTags as $tag)
                         <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 border-l-4 border-white/40">
                             <div class="flex items-start justify-between">
                                 <div class="flex-1">
                                     <div class="flex items-center gap-2 mb-2">
                                         <div
                                             class="w-8 h-8 bg-white/30 rounded-full flex items-center justify-center text-white font-semibold text-xs">
                                             {{ \Str::limit($tag->taggedByUser->name, 2, '') }}
                                         </div>
                                         <div>
                                             <span class="font-medium">{{ $tag->taggedByUser->name }}</span>
                                             <span class="text-sm opacity-80">menandai Anda</span>
                                         </div>
                                     </div>

                                     <div class="bg-white/20 rounded-lg p-3 mb-3">
                                         <p class="text-white/90 text-sm">
                                             {{ \Str::limit($tag->message->content, 150) }}
                                         </p>
                                     </div>

                                     <div class="text-xs opacity-80 mb-3">
                                         {{ $tag->created_at->diffForHumans() }}
                                     </div>

                                     <div class="flex gap-2">
                                         <button wire:click="markTagAsRead({{ $tag->id }})"
                                             class="px-3 py-1 bg-white/20 hover:bg-white/30 text-white rounded-lg text-xs font-medium transition-colors">
                                             Tandai Dibaca
                                         </button>

                                         <button onclick="scrollToMessage({{ $tag->message->id }})"
                                             wire:click="closeTagsModal"
                                             class="px-3 py-1 bg-emerald-600/70 hover:bg-emerald-600 text-white rounded-lg text-xs font-medium transition-colors">
                                             Lihat Pesan
                                         </button>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     @endforeach
                 @else
                     <div class="text-center py-12 opacity-90">
                         <svg class="w-16 h-16 mx-auto mb-4 text-white/60" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                 d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                         </svg>
                         <p class="font-semibold">Tidak ada tag baru</p>
                         <p class="text-sm opacity-80 mt-1">Anda belum ditandai dalam chat ini</p>
                     </div>
                 @endif
             </div>
         </div>
     @endif



     <script>
         function scrollToMessage(messageId) {
             // Scroll to specific message (implement based on your message display structure)
             const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
             if (messageElement) {
                 messageElement.scrollIntoView({
                     behavior: 'smooth',
                     block: 'center'
                 });
                 messageElement.classList.add('highlight-message');
                 setTimeout(() => {
                     messageElement.classList.remove('highlight-message');
                 }, 3000);
             }
         }

         // Real-time tag notifications listener
         document.addEventListener('livewire:initialized', () => {
             // Listen untuk event tag baru
             Livewire.on('new-tag-received', () => {
                 // Force refresh component untuk update badge
                 @this.refreshTagNotifications();
             });

             // Listen untuk event tag dibaca
             Livewire.on('tag-marked-as-read', () => {
                 // Component akan auto-refresh karena sudah ada listener
             });
         });

         // Show notification toast untuk tag baru (opsional)
         function showTagNotification(taggerName) {
             // Bisa ditambahkan toast notification di sini
             console.log(`${taggerName} mentioned you in a message`);
         }
     </script>

     <style>
         .highlight-message {
             background-color: rgba(59, 130, 246, 0.1);
             border: 2px solid rgba(59, 130, 246, 0.3);
             border-radius: 12px;
             animation: highlight-pulse 2s ease-in-out;
         }

         @keyframes highlight-pulse {

             0%,
             100% {
                 opacity: 1;
             }

             50% {
                 opacity: 0.7;
             }
         }

         .animate-pulse-new-tag {
             animation: pulse-new-tag 2s infinite;
         }

         @keyframes pulse-new-tag {

             0%,
             100% {
                 transform: scale(1);
                 box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
             }

             50% {
                 transform: scale(1.05);
                 box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
             }
         }

         /* Bounce animation untuk badge counter */
         .animate-bounce {
             animation: bounce 1s infinite;
         }

         @keyframes bounce {

             0%,
             20%,
             53%,
             80%,
             100% {
                 transform: translate3d(0, 0, 0);
             }

             40%,
             43% {
                 transform: translate3d(0, -8px, 0);
             }

             70% {
                 transform: translate3d(0, -4px, 0);
             }

             90% {
                 transform: translate3d(0, -2px, 0);
             }
         }
     </style>

 </div>
