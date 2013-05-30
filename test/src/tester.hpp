#pragma once

#include <string>
#include <stdexcept>
#include <iostream>
#include <fstream>

#include <boost/optional.hpp>
#include <boost/asio.hpp>
#include <boost/process.hpp>
#include <boost/date_time/posix_time/posix_time.hpp>

namespace test
{
	class tester
	{
	private:
		tester() = delete;
		tester(tester&) = delete;
		void operator=(tester&) = delete;
	
		static void generate_model(size_t n, size_t f)
		{
			namespace bp = boost::process;
			namespace bpi = boost::process::initializers;
			
			std::stringstream args;
			args << "php input.smv.php " << n << ' ' << f;
			
			boost::iostreams::file_descriptor_sink sink("tmp.smv");
			auto c = bp::execute(
				bpi::run_exe("/usr/bin/php"),
				bpi::set_cmd_line(args.str()),
				bpi::bind_stdout(sink)
			);
			
			bp::wait_for_exit(c);
		}
		
		static boost::optional<boost::posix_time::time_duration> test(std::string args)
		{
			namespace bp = boost::process;
			namespace bpi = boost::process::initializers;
			namespace bpt = boost::posix_time;
			
			boost::optional<bpt::time_duration> result;
			
			std::stringstream s;
			s << "./NuSMV " << args;
			
			boost::asio::io_service io;
			boost::asio::signal_set set(io, SIGCHLD);
			
			auto start = bpt::microsec_clock::universal_time();
			
			set.async_wait(
				[&](const boost::system::error_code&, int)
				{
					int status;
					::wait(&status);
					if(status == 0)
					{
						std::cerr << "exited gracefully" << std::endl;
						result = bpt::microsec_clock::universal_time() - start;
					}
					
					io.stop();
				}
			);
			
			auto c = bp::execute(
				bpi::run_exe("/home/wgeraedts/src/NuSMV-2.5.4/nusmv/NuSMV"),
				bpi::set_cmd_line(s.str()),
				bpi::close_stdout(),
				bpi::notify_io_service(io)
			);

			boost::asio::deadline_timer t(io, boost::posix_time::seconds(120));
			t.async_wait([&](const boost::system::error_code&) {
				bp::terminate(c);
				std::cerr << "killed" << std::endl;
			});

			io.run();
			return result;
		}
		
	public:
		static void execute()
		{
			for(size_t n = 1; n <= 5; ++n)
				for(size_t f = 2; f <= 10; ++f)
				{
					generate_model(n, f);
					std::cout << "n=" << n << " f=" << f << std::endl;
					auto result = test("-t /home/wgeraedts/git/elevatormodel/model/elevator_empty.ord tmp.smv");
					
					if(!result)
					{
						std::cout << "infinite" << std::endl;
						break;
					}
					else
						std::cout << result.get() << std::endl;
				}
		}
	};
}
