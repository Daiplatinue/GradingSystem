<?php if (!empty($pending_appeals)): ?>
    <div class="space-y-4">
        <?php foreach ($pending_appeals as $appeal): ?>
            <div class="bg-[#f5f5f7] rounded-xl p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-lg font-semibold text-[#1d1d1f]">
                            <?php echo htmlspecialchars($appeal['a_fn']); ?>
                        </h3>
                        <p class="text-[#86868b] text-sm">
                            <?php echo htmlspecialchars($appeal['a_email']); ?>
                        </p>
                        <p class="mt-2 text-[#1d1d1f]">
                            <?php echo htmlspecialchars($appeal['ap_message']); ?>
                        </p>
                    </div>
                    <div class="flex space-x-2">
                        <form method="POST" class="inline">
                            <input type="hidden" name="appeal_id" value="<?php echo $appeal['ap_id']; ?>">
                            <input type="hidden" name="status" value="Accepted">
                            <button type="submit" name="update_appeal_status"
                                class="px-4 py-2 bg-[#34c759] text-white rounded-lg hover:bg-[#30b753] transition-colors">
                                Accept
                            </button>
                        </form>
                        <form method="POST" class="inline">
                            <input type="hidden" name="appeal_id" value="<?php echo $appeal['ap_id']; ?>">
                            <input type="hidden" name="status" value="Declined">
                            <button type="submit" name="update_appeal_status"
                                class="px-4 py-2 bg-[#ff3b30] text-white rounded-lg hover:bg-[#e6352b] transition-colors">
                                Decline
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="text-center py-8 text-[#86868b]">
        No pending appeals at this time.
    </div>
<?php endif; ?>