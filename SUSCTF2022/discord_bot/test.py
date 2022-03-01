import asyncio
from discord.ext import commands, tasks
from discord import utils
import discord
import json
import requests
import re


class SUSBot(commands.Bot):
    def __init__(self, evt_id, command_prefix, **options):
        super().__init__(command_prefix, **options)
        self.blood_channel = None
        self.report_channel = None
        self.action_channel = None
        self.guild = None
        self.evt_id = evt_id
        self.id = 2266


class BotCog(commands.Cog):
    def __init__(self, bot):
        self.bot = bot
        self.first_blood.start()

    def cog_unload(self):
        self.first_blood.cancel()

    @tasks.loop(seconds=60)
    async def first_blood(self):
        try:
            event_url = "https://adworld.xctf.org.cn/api/evts/notices?evt={}&id={}".format(bot.evt_id, bot.id)
            # TODO session要临时填
            res = requests.get(event_url, cookies={"session": "**************************"})
            print(res.text)
            print(bot.id)
            events = json.loads(res.text)
            temp_id = events[0]['id']
            for event in events:
                # 这个id有点怪，似乎是获取到大于当前id的所有通知，同时被发布在通知栏的通知总是会被获取到
                # 但是目测id是降序排的，所以最前面的id应该就是最新的事件
                if event['id'] > bot.id:
                    match = re.match("^(.*?) got first blood of \[(.*?)\]$", event["notice_en"])
                    if match:
                        await bot.blood_channel.send(":first_place: `{}` got the first blood of `{}`, congratulation! :tada::tada::tada:".format(match.groups()[0], match.groups()[1]))
                else:
                    break
            # 玄学，接口只查得出大于当前id的，故减一
            bot.id = temp_id
            # print(bot.id)
        except Exception as e:
            print(e)
            # await bot.report_channel.send(e)


# 玄学代理解决方案
# asyncio.set_event_loop_policy(asyncio.WindowsSelectorEventLoopPolicy())
description = """test bot for SUSCTF
"""

# 刚才去攻防世界看的是171
bot = SUSBot(171, command_prefix=">", description=description, help_command=None)
cog = None

@bot.event
async def on_ready():
    # 测试用服务器
    # bot.guild = bot.get_guild(945565285157601312)
    # 比赛用服务器,慎用!!!
    bot.guild = bot.get_guild(941157903128223805)
    bot.blood_channel = utils.get(bot.guild.text_channels, name="first-blood")
    # bot.blood_channel = utils.get(bot.guild.text_channels, name="main-chat")
    bot.report_channel = utils.get(bot.guild.text_channels, name="main-chat")
    # bot.action_channel = utils.get(bot.guild.text_channels, name="main-chat")
    bot.action_channel = utils.get(bot.guild.text_channels, name="challenge-checkin")
    print('bot {} ready!'.format(bot.user))
    await bot.report_channel.send("bot {} ready".format(bot.user))


def channel_check(message):
    # 确保bot只在对应的channel接受命令
    return message.channel == bot.action_channel or message.channel == bot.report_channel or isinstance(message.channel, discord.DMChannel)


@bot.command()
@commands.check(channel_check)
async def ping(message):
    await message.send("pong!")


@bot.command()
@commands.check(channel_check)
async def hello(message):
    await message.send("welcome to SUSCTF, {}!".format(message.author))


@bot.command()
@commands.check(channel_check)
async def echo(message, arg):
    if "@" in arg:
        await message.send("no at!")
    else:
        await message.send(arg)


@bot.command()
@commands.check(channel_check)
async def aa(message):
    await message.send("command not found")


@bot.command()
@commands.check(channel_check)
async def bb(message):
    await message.send("you found it!")


@bot.command()
@commands.check(channel_check)
async def help(message):
    h = """```
bot for SUSCTF
    ping: Pong!
    echo: Echo input
    hello: Welcome to you
    flag: Give you flag
    help: Shows this message
    start: Start this bot
    stop: Stop this bot
    exit: Exit this bot
```"""
    await message.send(h)


@bot.command()
@commands.check(channel_check)
async def flag(message):
    # 这个操作会直接私聊
    if not isinstance(message.channel, discord.DMChannel):
        await message.send("PM me and I'll give it to you secretly")
    else:
        message = await message.author.send("SUSCTF{0oooOh!you_cAtched_the_FIag_wh1ch_1s_not_ez_to_r3m3mb3r}")
        await message.edit(content="Oops, it disappear!")


@bot.command()
@commands.check(channel_check)
async def start(message):
    if message.author.id == 811150335175163914:
        global cog
        cog = BotCog(bot)
        bot.add_cog(cog)
        await bot.report_channel.send("bot {} start".format(bot.user))
        print("bot {} start".format(bot.user))
    else:
        await message.send("?")
        await message.send("reporting {} to admin".format(message.author))

@bot.command()
@commands.check(channel_check)
async def stop(message):
    # 我自己的id
    if message.author.id == 811150335175163914:
        await bot.report_channel.send("bot {} stop".format(bot.user))
        bot.remove_cog("BotCog")
        print("bot {} stop".format(bot.user))
    else:
        await message.send("?")
        await message.send("reporting {} to admin".format(message.author))

@bot.command()
@commands.check(channel_check)
async def exit(message):
    if message.author.id == 811150335175163914:
        await bot.report_channel.send("bot {} exit".format(bot.user))
        await bot.close()
        print("bot {} exit".format(bot.user))
    else:
        await message.send("?")
        await message.send("reporting {} to admin".format(message.author))

bot.run('**************************')
