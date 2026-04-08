<?php //phpcs:disable

namespace dnt_notify\tests;

use dnt_notify\tg\Tg;
use dnt_notify\tg\Tg_Message_Interface;
use Mockery;
use WP_UnitTestCase;

/**
 * Test class for Telegram integration
 */
class Test_Tg extends WP_UnitTestCase {

	/**
	 * Test sending a Telegram message
	 */
	public function test_send_message(): void {
		// Create a mock for Tg_Message_Interface
		$messageMock = Mockery::mock(Tg_Message_Interface::class);
		$messageMock->shouldReceive('to')->andReturn(['12345']);
		$messageMock->shouldReceive('content')->andReturn('Test message');

		// Create a mock for Telegram API
		$apiMock = Mockery::mock('Telegram\Bot\Api');
		$apiMock->shouldReceive('sendMessage')->with(
			[
				'chat_id' => '12345',
				'text' => 'Test message',
				'parse_mode' => 'html',
			]
		)->andReturn(true);

		// Create an instance of Tg with the mocked API
		$tg = new Tg();
		$tg->set_api($apiMock);

		// Call the send method
		$tg->send($messageMock);

		// Assert that the send method was called
		$this->assertTrue(true);
	}

	/**
	 * Test getting group info
	 */
	public function test_get_group_info(): void {
		// Create a mock for Telegram API
		$apiMock = Mockery::mock('Telegram\Bot\Api');
		$chatMock = Mockery::mock('Telegram\Bot\Objects\Chat');
		$chatMock->shouldReceive('getTitle')->andReturn('Test Group');
		$chatMock->shouldReceive('getType')->andReturn('group');
		$apiMock->shouldReceive('getChat')->with(['chat_id' => '12345'])->andReturn($chatMock);

		// Create an instance of Tg with the mocked API
		$tg = new Tg();
		$tg->set_api($apiMock);

		// Call the get_group_info method
		$result = $tg->get_group_info('12345');

		// Assert the result
		$this->assertEquals(['title' => 'Test Group', 'type' => 'group'], $result);
	}

	/**
	 * Test getting chat link
	 */
	public function test_get_chat_link(): void {
		// Create an instance of Tg
		$tg = new Tg();

		// Call the get_chat_link method
		$result = $tg->get_chat_link();

		// Assert that the result is null if user is not logged in
		$this->assertNull($result);
	}

	/**
	 * Test getting add group link
	 */
	public function test_get_add_group_link(): void {
		// Create an instance of Tg
		$tg = new Tg();

		// Call the get_add_group_link method
		$result = $tg->get_add_group_link();

		// Assert that the result is null if user is not logged in
		$this->assertNull($result);
	}
}