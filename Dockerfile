FROM fabiokung/cedar
MAINTAINER Ryan Brainard

# Run Buildpack: bin/compile
RUN mkdir -p /var/lib/buildpack
RUN mkdir -p /var/cache/buildpack
RUN git clone https://github.com/ryanbrainard/heroku-buildpack-phing.git -b openfifo /var/lib/buildpack
ADD ./build.xml /app/build.xml
ADD ./Procfile /app/Procfile
ADD ./workbench /app/workbench
ENV HOME /app
RUN /var/lib/buildpack/bin/compile /app /var/cache/buildpack

# Run Buildpack: bin/release
ENV APP_LOG_FILE /app/target/app.log
ENV LD_LIBRARY_PATH /app/php/ext
ENV PATH /app/php/bin:/usr/local/bin:/usr/bin:/bin

# Configure
ENV forceworkbench__enableLogging__default true
ENV forceworkbench__logFile__default /app/target/app.log
ENV forceworkbench__logHandler__default file
ENV forceworkbench__logPrefix__default forceworkbench-docker

# Run app
EXPOSE 8080
ENV PORT 8080
WORKDIR /app
CMD sh boot.sh
