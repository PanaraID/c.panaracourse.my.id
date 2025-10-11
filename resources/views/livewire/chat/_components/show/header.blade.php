 <?php
 
 use Livewire\Volt\Component;
 use App\Models\Chat;
 
 new class extends Component {
     public $chat;
 
     public function mount(Chat $chat)
     {
         $this->chat = $chat;
     }
 };
 
 ?>

 <div
     class="bg-gradient-to-r from-emerald-500 via-green-500 to-teal-500 dark:from-emerald-700 dark:via-green-700 dark:to-teal-700 px-6 py-4 shadow-lg backdrop-blur-sm">
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

             <!-- Chat Avatar with Gradient -->
             <div class="relative">
                 <div
                     class="w-12 h-12 bg-gradient-to-br from-white/30 to-white/10 backdrop-blur-md rounded-full flex items-center justify-center ring-4 ring-white/30 shadow-lg animate-float">
                     <svg class="w-7 h-7 text-white drop-shadow-md" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                             d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2m5-8a3 3 0 110-6 3 3 0 010 6m5 3a2 2 0 11-4 0 2 2 0 014 0z">
                         </path>
                     </svg>
                 </div>
                 <div
                     class="absolute bottom-0 right-0 w-3.5 h-3.5 bg-green-400 rounded-full border-2 border-white animate-pulse-slow">
                 </div>
             </div>

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
 </div>
