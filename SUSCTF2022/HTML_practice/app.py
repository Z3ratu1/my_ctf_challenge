from mako.template import Template
import web
import re
import random
import string

urls = (
    '/', 'Index',
    '/view/(.*)', 'View',
    '/generate', 'Generate'
)
web.config.debug = False


class MyApplication(web.application):
    def run(self, port=80, *middleware):
        func = self.wsgifunc(*middleware)
        return web.httpserver.runsimple(func, ('0.0.0.0', port))


class Index:
    def GET(self):
        return Template(filename='./static/index.html').render()


class Generate:
    def POST(self):
        data = web.input()
        if re.findall(
                r'\$|\'|"|\[|\]|_|\+|%>|eval|exec|byte|str|open|code|class|globals|\*|\-|\/|chr|join|ascii|repr|ord|hex|oct|bin|local|dir|list|vars|if|type',
                data.html):
            return 'hacker!'
        filename = ''.join(random.sample(string.ascii_letters + string.digits, 32)) + '.html'
        content = (data.html + '<p>ur welcome, ${name}!</p>').encode()

        file = open('template/' + filename, 'wb')
        file.write(content)
        file.close()
        url = './view/' + filename + '?name=HelloWorld'
        return Template(filename='./static/generate.html').render(url=url)


class View:
    def GET(self, text):
        data = web.input()
        if data.name:
            name = data.name
        else:
            name = 'HelloWorld'
        if len(name) > 99:
            return 'name to long!'
        return Template(filename='./template/' + text).render(name=name)


if __name__ == "__main__":
    app = MyApplication(urls, globals())
    app.run()
