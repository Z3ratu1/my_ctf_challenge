const express = require('express')
const ejs = require('ejs')
const puppeteer = require('puppeteer')
// request库是什么臭鱼烂虾，await都不支持
const axios = require('axios')
const bots = require('./bots')


const app = express()
app.set('view engine', 'ejs')
app.use(express.urlencoded({extended: false}))

app.get('/:cha', async (req, res, next) => {
    for (let bot of Object.values(bots)) {
        if (bot.opt.router === req.params.cha) {
            res.render(bot.opt.template ?? "index", bot.opt)
            return;
        }
    }
    // 处理404
    next()
})

app.post("/:cha", async (req, res, next) => {
    for (let bot of Object.values(bots)) {
        if (bot.opt.router === req.params.cha) {
            let renderOpt = {...bot.opt}
            // check一下captcha
            if (req.body['g-recaptcha-response']) {
                let secret = "**************************"
                // 试试国内镜像能不能用
                let verificationUrl = "https://www.recaptcha.net/recaptcha/api/siteverify?secret=" + secret + "&response=" + req.body['g-recaptcha-response'] + "&remoteip=" + req.socket.remoteAddress;
                let solved
                try {
                    let response = await axios.get(verificationUrl, {timeout: 3000})
                    // let body = JSON.parse(response.data)
                    // Success will be true or false depending upon captcha validation.
                    // if (body.success === undefined || !body.success) {
                    if (response.data.success === undefined || !response.data.success) {
                        solved = 0
                    } else {
                        solved = 1
                    }
                } catch (e) {
                    console.log(e.message)
                    // 访问不上，先不给过
                    solved = 0
                }
                if (solved) {
                    try {
                        let browser = await puppeteer.launch({headless: true})
                        let opt = await bot.visit(browser, req.body.url.toString())
                        res.render(bot.opt.template ?? "index", opt)
                        for (const tmppage of await browser.pages()) {
                            if (!tmppage.isClosed()) {
                                // 启动时会自带一个about:blank，当全部页面关闭时browser会退出
                                // goto about:blank之后会释放掉当前页面的所有资源
                                await tmppage.goto('about:blank')
                                await tmppage.close()
                            }
                        }
                        await browser.close()
                        return;
                    } catch (e) {
                        console.log(e)
                        break
                    }
                } else {
                    renderOpt.message = "Failed captcha verification"
                    res.render(bot.opt.template ?? "index", renderOpt)
                    return;
                }
            } else {
                renderOpt.message = "Please finish captcha"
                res.render(bot.opt.template ?? "index", renderOpt)
                return;
            }
        }
    }
    // 处理404
    next()
})

app.use((req, res, next) => {
    res.status(500).send("error occurred")
})

app.listen(3000, () => {
    console.log("app start")
    console.log("registered bots:")
    for (let bot of Object.values(bots)) {
        console.log(bot.opt.router)
    }
})


