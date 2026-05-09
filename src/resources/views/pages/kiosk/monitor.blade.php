@extends('layouts.monitor')

<style>
    :root {
        --main-blue: #003366;
        --accent-blue: #0056b3;
    }

    body { overflow: hidden; background: #f0f2f5; }

    .monitor-header {
        background: white;
        padding: 1rem 2rem;
        border-bottom: 3px solid var(--main-blue);
    }

    .monitor-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        background: white;
    }

    .number-huge {
        font-size: 18rem;
        font-weight: 900;
        color: #1a1a1a;
        line-height: 1;
        margin: 20px 0;
    }

    .loket-pill {
        font-size: 3.5rem;
        font-weight: 800;
        background: var(--main-blue);
        color: #ffffff !important; /* Paksa Putih */
        padding: 15px 60px;
        border-radius: 100px;
        display: inline-block;
        box-shadow: 0 8px 15px rgba(0,51,102,0.2);
    }

    .name-text {
        font-size: 2.5rem;
        color: #666;
        font-weight: 500;
        text-transform: uppercase;
    }

    .monitor-footer {
        background: white;
        padding: 10px 30px;
        font-size: 1.2rem;
    }
</style>

@section('content')
<div class="monitor-container vh-100 d-flex flex-column">
    <img src="{{ asset('tabler/img/logo/LaporMasWapres.png') }}" alt="Logo" class="d-block mx-auto py-3" style="height: 100px; width: auto;">
    <div class="flex-grow-1 p-3">
        <div class="row h-100 g-3">
            <div class="col-lg-3">
                @livewire('admin.monitor-display')
            </div>

            <div class="col-lg-6">
                <div class="monitor-card h-100 d-flex flex-column align-items-center justify-content-center text-center">
                    <div class="text-muted h2 fw-bold text-uppercase mb-0">Nomor Antrean</div>
                    <div id="big-number" class="number-huge">
                        {{ $currentCall->queue_number ?? '---' }}
                    </div>
                    <div id="big-loket" class="loket-pill">
                        LOKET {{ $currentCall->counter_number ?? '-' }}
                    </div>
                    <div id="big-name" class="name-text mt-4">
                        {{ $currentCall->name ?? 'Silakan Mengambil Antrean' }}
                    </div>
                </div>
            </div>

            <div class="col-lg-3 h-100 d-flex flex-column gap-3">
                <div class="p-0">
                    @livewire('admin.queue-counter')
                </div>

                <div class="card monitor-card border-0 shadow-sm flex-grow-1 d-flex flex-column" style="border-radius: 12px; overflow: hidden;">
                    <div class="card-header py-3" style="background: #003366;">
                        <h3 class="card-title fw-bold mb-0 text-white" style="font-size: 1.1rem;">
                            <i class="ti ti-history me-2"></i>Riwayat Panggilan
                        </h3>
                    </div>
                    
                    <div class="card-body p-2 flex-grow-1" style="overflow-y: auto; background: #ffffff;">
                        @livewire('admin.monitor-history-list')
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="monitor-footer d-flex justify-content-between align-items-center border-top">
        <div class="badge bg-green-lt p-2 px-3 h4 mb-0">Sistem Real-time Aktif</div>
        <div id="live-clock" class="fw-bold h3 mb-0 text-dark"></div>
    </div>
</div>

@push('scripts')
<audio id="chime-sound" src="{{ asset('assets/audio/bell.mp3') }}" preload="auto"></audio>
<script>
    let voiceEnabled = false;

    function updateClock() {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
        const element = document.getElementById('live-clock');
        if(element) element.innerText = now.toLocaleDateString('id-ID', options).replace(/,/g, ' •');
    }
    setInterval(updateClock, 1000);

    document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
            title: 'SISTEM SIAP',
            text: 'Tekan ENTER atau SPACE pada keyboard untuk mengaktifkan suara monitor.',
            icon: 'success',
            showConfirmButton: true,
            confirmButtonText: 'AKTIFKAN (ENTER)',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) { activateVoice(); }
        });

        document.addEventListener('keydown', function(event) {
            if (event.code === 'Enter' || event.code === 'Space') {
                Swal.close();
                activateVoice();
            }
        });

        function activateVoice() {
            voiceEnabled = true;
            const msg = new SpeechSynthesisUtterance('');
            window.speechSynthesis.speak(msg);
            console.log('Suara diaktifkan via keyboard/klik');
        }

        const initEchoListener = setInterval(() => {
            if (typeof Echo !== 'undefined') {
                clearInterval(initEchoListener);
                console.log('Echo Terdeteksi! Menghubungkan ke kiosk-channel...');

                Echo.channel('kiosk-channel')
                    .listen('.AntreanDipanggil', (data) => {
                        console.log('Data diterima:', data);
                        
                        const numEl = document.getElementById('big-number');
                        const loketEl = document.getElementById('big-loket');
                        const nameEl = document.getElementById('big-name');

                        if(numEl) numEl.innerText = data.queueNumber;
                        if(loketEl) loketEl.innerText = 'LOKET ' + data.counterNumber;
                        if(nameEl) nameEl.innerText = data.name;

                        if (voiceEnabled) {
                            playVoice(data.queueNumber, data.counterNumber);
                        } else {
                            console.warn('Suara belum diaktifkan oleh pengguna.');
                        }

                        if (window.Livewire) window.Livewire.dispatch('refreshComponent');
                    });
            } else {
                console.log('Menunggu Echo dimuat oleh Vite...');
            }
        }, 500);
    });

    function playVoice(queueNumber, counter) {
        if ('speechSynthesis' in window) {
            window.speechSynthesis.cancel();
            const chime = document.getElementById('chime-sound');
            
            if (chime) {
                chime.currentTime = 0;
                chime.play().then(() => {
                    chime.onended = function() {
                        setTimeout(() => { startSpeaking(queueNumber, counter); }, 800);
                    };
                }).catch(e => {
                    console.error("Gagal putar chime:", e);
                    startSpeaking(queueNumber, counter);
                });
            } else {
                startSpeaking(queueNumber, counter);
            }
        }
    }

    function startSpeaking(queueNumber, counter) {
        const spelled = queueNumber.toString().split('').join(' ');
        const text = `Nomor antrean, ${spelled}, silakan menuju loket, ${counter}`;
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'id-ID';
        utterance.rate = 0.8; 
        
        const voices = window.speechSynthesis.getVoices();
        const idVoice = voices.find(v => v.lang.includes('id') || v.lang.includes('ID'));
        if (idVoice) utterance.voice = idVoice;
        
        window.speechSynthesis.speak(utterance);
    }
</script>
@endpush
@endsection