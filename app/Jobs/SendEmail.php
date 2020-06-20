<?php

namespace App\Jobs;

use App\Campaign;
use App\Contact;
use App\Jobs\Middleware\RateLimited;
use App\Mail\CampaignMail;
use App\SendingLog;
use Carbon\Carbon;
use DOMDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $campaign;

    protected $contact;

    protected $config;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Contact $contact, Campaign $campaign, $config)
    {
        $this->campaign = $campaign;
        $this->contact = $contact;
        $this->config = $config;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $mail_content = $this->mailContent();

        $send = SendingLog::create([
            'contact_id' => $this->contact->id,
            'campaign_id' => $this->campaign->id,
            'sent_at' => Carbon::now()
        ]);

        Mail::config($this->config)->to($this->contact->email)->send(new CampaignMail($this->campaign, $send->id));

        
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware()
    {
        return [new RateLimited];
    }

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime
     */
    public function retryUntil()
    {
        return now()->addDay();
    }

    protected function mailContent()
    {
        if ($this->campaign->track_clicks) {
            return $this->addContactIdToTrackingLinks();
        }

        return $this->campaign->content;
    }

    protected function addContactIdToTrackingLinks()
    {
        $dom = new DOMDocument();

        $dom->loadHTML($this->campaign->content);

        foreach ($dom->getElementsByTagName('body')[0]->getElementsByTagName('a') as $link) {
            $oldLink = $link->getAttribute('href');

            $newLink = $oldLink.'/'.$this->contact->uuid;

            $link->setAttribute('href', $newLink);
        }

        return $dom->saveHtml();
    }
}
