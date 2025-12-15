<x-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Chirp Management</h1>
            <p class="mt-2 text-gray-600">View, edit, and manage all chirps on the platform</p>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        <div class="space-y-6">
            @forelse($chirps as $chirp)
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <span class="font-semibold text-gray-900">{{ $chirp->user ? $chirp->user->name : 'Anonymous' }}</span>
                                <span class="mx-2 text-gray-400">•</span>
                                <span class="text-sm text-gray-600">{{ $chirp->created_at->format('M d, Y g:i A') }}</span>
                                @if($chirp->created_at != $chirp->updated_at)
                                    <span class="ml-2 text-xs text-gray-500">(edited)</span>
                                @endif
                            </div>
                            <p class="text-gray-800">{{ $chirp->message }}</p>
                        </div>
                        <div class="flex items-center space-x-3 ml-4">
                            <a href="{{ route('admin.chirps.edit', $chirp) }}" 
                               class="text-blue-600 hover:text-blue-800 text-sm font-medium btn">
                                Edit
                            </a>
                            <form action="{{ route('admin.chirps.destroy', $chirp) }}" method="POST" 
                                  onsubmit="return confirm('Are you sure you want to delete this chirp?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium btn">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-lg shadow p-6 text-center text-gray-600">
                    No chirps found.
                </div>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $chirps->links() }}
        </div>
    </div>
</x-layout>